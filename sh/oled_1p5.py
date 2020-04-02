#!/usr/bin/env python

import sys, os, time, json, datetime, logging
from luma.core import cmdline, error
from luma.core.legacy import text, textsize
from luma.core.legacy.font import LCD_FONT
from luma.core.render import canvas
from PIL import Image, ImageDraw, ImageFont
from gpiozero import Button

done = False
data = None
offGPIO = 10
holdTime = 1
page = 0
max_page = 2
page_timer = None
page_timeout = datetime.timedelta(seconds=10)
BORDER=False

# logging
logging.basicConfig(
	level=logging.DEBUG,
	format='%(asctime)-15s - %(message)s'
)
logging.getLogger('PIL').setLevel(logging.ERROR)


def logger(str):
	debug = True
	if debug:
		print(str)


def display_settings(args):
    iface = ''
    display_types = cmdline.get_display_types()
    if args.display not in display_types['emulator']:
        iface = 'Interface: {}\n'.format(args.interface)

    lib_name = cmdline.get_library_for_display_type(args.display)
    if lib_name is not None:
        lib_version = cmdline.get_library_version(lib_name)
    else:
        lib_name = lib_version = 'unknown'

    import luma.core
    version = 'luma.{} {} (luma.core {})'.format(
        lib_name, lib_version, luma.core.__version__)

    return 'Version: {}\nDisplay: {}\n{}Dimensions: {} x {}\n{}'.format(
        version, args.display, iface, args.width, args.height, '-' * 60)


def get_device(actual_args=None):
    """
    Create device from command-line arguments and return it.
    """
    if actual_args is None:
        actual_args = sys.argv[1:]
    parser = cmdline.create_parser(description='luma.examples arguments')
    args = parser.parse_args(actual_args)

    if args.config:
        # load config from file
        config = cmdline.load_config(args.config)
        args = parser.parse_args(config + actual_args)

    print(display_settings(args))

    # create device
    try:
        device = cmdline.create_device(args)
    except error.Error as e:
        parser.error(e)

    return device


def drawText(draw, txt, ypos, size=12):
	return drawPrettyText(draw, txt, size=size, ypos=ypos)
	(font_width, font_height) = textsize(txt, font=LCD_FONT)
	text(draw, (1+(device.width/2 - font_width/2), ypos), txt, fill="white", font=LCD_FONT)
	return ypos + font_height + 5


def drawPrettyText(draw, txt, size, ypos):
	scale = 1;
	font = ImageFont.truetype(os.path.dirname(os.path.abspath(__file__))+"/../fonts/andalemo.ttf", scale*size)
	(font_width, font_height) = font.getsize(txt)

	draw.text(((device.width/2 - font_width/2), ypos), txt, fill="white", font=font)
	return ypos + size + 3


def drawTrigger(key, data, draw):
	sp=128/6;
	xp = (key-0.5)*sp;
	yp = device.height -10 - 1*sp;
	r  = sp-8;

	outl = "#101010"
	fill = "#000"
	kk = "TRIGGER.T" + str(key)
	if kk in  data:
		outl = "#333" if data[kk] else "#333"
		fill = "#fff" if data[kk] else "#101010"

	draw.ellipse((xp-r/2, yp-r/2, xp+r/2, yp+r/2), fill = fill, outline = outl)


def floatValue(key, data, default = " -- "):
	return '{:4.1f}'.format(float(data[key])) if key in data else default


def drawData(data):
	if done:
		return

	logger("refresh")

	ypos = 1;
	with canvas(device) as draw:
		#canvas.fontmode = 1
		if BORDER:
			draw.rectangle((0, 0, device.width-1, device.height-1), outline="white", fill="black")

		ypos = drawText(draw, data["INFO.IPADDR"], ypos)

		if page == 0:
			ypos = drawText(draw, data["INFO.NEXTSUN"], ypos)
			ypos = ypos + 4

			z1x = '{}'.format(" Z1 ") if 'ZONE1.TEMPERATURE' in data else " -- "
			z2x = '{}'.format(" Z2 ") if 'ZONE2.TEMPERATURE' in data else " -- "
			z3x = '{}'.format(" Z3 ") if 'ZONE3.TEMPERATURE' in data else " -- "
			ypos = drawText(draw, "  {} {} {}".format(z1x, z2x, z3x), ypos)
			#ypos = drawText(draw, "   Z1   Z2   Z3 ", ypos)

			ypos = drawText(draw, "T {} {} {}".format(floatValue("ZONE1.TEMPERATURE", data), floatValue("ZONE2.TEMPERATURE", data), floatValue("ZONE3.TEMPERATURE", data)), ypos)
			ypos = drawText(draw, "H {} {} {}".format(floatValue("ZONE1.HUMIDITY", data), floatValue("ZONE2.HUMIDITY", data), floatValue("ZONE3.HUMIDITY", data)), ypos)

			for i in range(1, 7):
				drawTrigger(i, data, draw);

		elif page == 1:
			ypos = drawText(draw, data["INFO.NEXTSUN"], ypos)
			ypos = ypos + 4
			ypos = drawText(draw, "CPU Load {}%".format(floatValue("PI.CPU_LOAD", data)), ypos)
			ypos = drawText(draw, "CPU Temp {}C".format(floatValue("PI.TEMPERATURE", data)), ypos)
			ypos = drawText(draw, "Mem Load {}%".format(floatValue("PI.MEM_LOAD", data)), ypos)
			ypos = drawText(draw, "SD Load  {}%".format(floatValue("PI.SD_LOAD", data)), ypos)

		else:
			ypos = drawText(draw, "Coming soon...", ypos)

		now = datetime.datetime.now()
		text = now.strftime("%Y-%m-%d %H:%M")
		drawText(draw, text, ypos=device.height-8, size=8)
def drawTest():
	logger("testing")
	ypos = 0;
	with canvas(device) as draw:
		draw.fontmode=1;
		ypos = drawPrettyText(draw, "000-000-000", 16, ypos)
		ypos = drawPrettyText(draw, "by", 8, ypos)
		ypos = drawPrettyText(draw, "000.000.000.000", 12, ypos)


def drawBoot():
	logger("booting")
	ypos = 0;
	with canvas(device) as draw:
		draw.fontmode=1;
		ypos = drawPrettyText(draw, "gStation", 16, ypos)
		ypos = drawPrettyText(draw, "by", 8, ypos)
		ypos = drawPrettyText(draw, "Tribal Rhino", 16, ypos)
		ypos = drawPrettyText(draw, "  ", 24, ypos)
		ypos = drawPrettyText(draw, "Starting up...", 12, ypos)

		#now = datetime.datetime.now()
		#text = now.strftime("%Y-%m-%d %H:%M")
		#font = ImageFont.truetype(os.path.dirname(os.path.abspath(__file__))+"/../fonts/andalemo.ttf", 12)
		#(font_width, font_height) = font.getsize(text)
		#draw.text(((device.width/2 - font_width/2), device.height-2-font_height), text, fill="white", font=font, dither=False)


def drawShutdown():
	ypos = 0;
	with canvas(device) as draw:
		ypos = drawPrettyText(draw, "gStation", 16, ypos)
		ypos = drawPrettyText(draw, "by", 8, ypos)
		ypos = drawPrettyText(draw, "Tribal Rhino", 16, ypos)
		ypos = drawPrettyText(draw, "  ", 12, ypos)
		ypos = drawPrettyText(draw, "Shutting", 12, ypos)
		ypos = drawPrettyText(draw, "Down...", 12, ypos)

		#now = datetime.datetime.now()
		#text = now.strftime("%Y-%m-%d %H:%M")
		#font = ImageFont.truetype(os.path.dirname(os.path.abspath(__file__))+"/../fonts/andalemo.ttf", 12)
		#(font_width, font_height) = font.getsize(text)
		#draw.text(((device.width/2 - font_width/2), device.height-2-font_height), text, fill="white", font=font, dither=False)


def nextPage():
	global page, max_page, page_timer, page_timeout, data

	page = page + 1;
	if page == max_page:
		page = 0

	logger("next page (" + str(page) + ")")
	page_timer = datetime.datetime.now() + page_timeout
	drawData(data)


def shutdown():
	global done
	done = True
	logger("shutdown")


def main():
	global data, page_timer, page
	btn = Button(offGPIO, hold_time=holdTime)
	btn.when_held = shutdown
	btn.when_pressed = nextPage

	drawBoot()
	#drawTest()
	time.sleep(3)

	triggerfile = '/tmp/oled.json'
	while not done:
		if page_timer != None:
			now = datetime.datetime.now()
			if now > page_timer:
				logger("page timeout")
				page = 0
				page_timer = None
				drawData(data)

		try:
			with open(triggerfile, "r") as f:
				data = json.load(f)
				os.remove(triggerfile)
				logger("loaded")
				logger(json.dumps(data, indent = 4, sort_keys=True))
				drawData(data)

		except FileNotFoundError:
			# No file yet, that's fine, carry on
			pass

		logger("loop")
		time.sleep(0.5)

	drawShutdown()
	time.sleep(3)
	device.clear()
 
	#os.system("sudo poweroff")
	sys.exit()

#	while True:
#		time.sleep(1) # while we wait for the shutdown loop to complete

if __name__ == "__main__":
	try:
		device = get_device()
		main()
	except KeyboardInterrupt:
		pass


