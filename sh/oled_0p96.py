#!/usr/bin/env python3

# Generall imports
import os, sys, time
from datetime import datetime

# For GPIOZero and a shutdown button
from gpiozero import Button

# For the OLED display
import board
import digitalio
from PIL import Image, ImageDraw, ImageFont
import adafruit_ssd1306


# digital button for pressing and the hold time.
offGPIO = 10
holdTime = 1

# Use the big display
WIDTH = 128
HEIGHT = 64
BORDER = 0

# Loop ender... When done, halt the system!!
done = False

def drawText(text):
	lines = text.split("|");
	
	# Create blank image for drawing.
	# Make sure to create image with mode '1' for 1-bit color.
	image = Image.new('1', (oled.width, oled.height))
    
	# Get drawing object to draw on image.
	draw = ImageDraw.Draw(image)

	if BORDER > 0 :
		# Draw a white background
		draw.rectangle((0, 0, oled.width, oled.height), outline=255, fill=255)

		# Draw a smaller inner rectangle
		draw.rectangle((BORDER, BORDER, oled.width - BORDER - 1, oled.height - BORDER - 1), outline=0, fill=0)

	# Draw Some Text
	font = ImageFont.truetype(os.path.dirname(os.path.abspath(__file__))+"/../fonts/andalemo.ttf", 14)
	(font_width, font_height) = font.getsize(lines[0])
	#draw.text((oled.width // 2 - font_width // 2, oled.height // 2 - font_height-14-5 ), lines[0], font=font, fill=255)
	draw.text((oled.width // 2 - font_width // 2, 0), lines[0], font=font, fill=255)

	(font_width, font_height) = font.getsize(lines[1])
	#draw.text((oled.width // 2 - font_width // 2, oled.height // 2 - font_height+6 ), lines[1], font=font, fill=255)
	draw.text((oled.width // 2 - font_width // 2, oled.height // 2 - font_height // 2 ), lines[1], font=font, fill=255)

	now = datetime.now()
	ts = now.strftime('%Y-%m-%d %H:%M')
	font = ImageFont.truetype(os.path.dirname(os.path.abspath(__file__))+"/../fonts/andalemo.ttf", 10)
	(font_width, font_height) = font.getsize(ts)
	#draw.text((oled.width // 2 - font_width // 2, oled.height // 2 + font_height+12 ), ts, font=font, fill=255)
	draw.text((oled.width // 2 - font_width // 2, oled.height - font_height ), ts, font=font, fill=255)
    
	# Display image
	oled.image(image)
	oled.show()

# the function called to shut down the RPI - just exit the loop and it;;l do the rest down there
def shutdown():
    # Gotta declare it global if you wanna write to it
    global done
    done = True
    drawText("SHUTDOWN|PRESSED")


# setup the callback
btn = Button(offGPIO, hold_time=holdTime)
btn.when_held = shutdown

# Use I2C for the OLED display
i2c = board.I2C()
oled = adafruit_ssd1306.SSD1306_I2C(WIDTH, HEIGHT, i2c, addr=0x3c)
#oled = adafruit_ssd1306.SSD1306_I2C(WIDTH, HEIGHT, i2c, addr=0x3d)

# Clear display.
oled.fill(0)
oled.show()

print("Starting OLED button trap")
drawText ("gStation|Starting...")

triggerfile = "/tmp/oled.txt"
while not done:
	try:
        # Try and read the file
		with open(triggerfile, "r") as f:
			line = f.readline()
			text = line.strip()

        # If we didn't excpetion then we have some content, remove the file, display the content
		os.remove(triggerfile)
		drawText(text)

	except FileNotFoundError:
        # No file yet, that's fine, carry on
		pass

    # Pause for a bit so we don't trip up anything
	time.sleep(0.5)

# If we got here then done was set to True in the shutdown function
print("SHUTDOWN")
# TODO: set the GPIOs back off 
os.system("sudo poweroff")
sys.exit()
