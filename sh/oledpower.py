#!/usr/bin/env python3

# For GPIOZero and a shutdown button
from gpiozero import Button
from signal import pause
import os, sys

# For the display
import board
import digitalio
from PIL import Image, ImageDraw, ImageFont
import adafruit_ssd1306

import time

# digital button for pressing and the hold time.
offGPIO = 21
holdTime = 1

# Text to display
done = False

def drawText(text):
    # Create blank image for drawing.
    # Make sure to create image with mode '1' for 1-bit color.
    image = Image.new('1', (oled.width, oled.height))
    
    # Get drawing object to draw on image.
    draw = ImageDraw.Draw(image)
    
    # Draw a white background
    draw.rectangle((0, 0, oled.width, oled.height), outline=255, fill=255)
    
    # Draw a smaller inner rectangle
    draw.rectangle((BORDER, BORDER, oled.width - BORDER - 1, oled.height - BORDER - 1), outline=0, fill=0)
    
    # Load default font.
    # font = ImageFont.load_default()
    font = ImageFont.truetype("../fonts/andalemo.ttf", 14)
     
    # Draw Some Text
    (font_width, font_height) = font.getsize(text)
    draw.text((oled.width // 2 - font_width // 2, oled.height // 2 - font_height // 2),
              text, font=font, fill=255)
    
    # Display image
    oled.image(image)
    oled.show()

# the function called to shut down the RPI - just exist for now
def shutdown():
	global done
	done = True
	drawText("SHUTDOWN")


# setup the callback
btn = Button(offGPIO, hold_time=holdTime)
btn.when_held = shutdown
# pause()  # handle the button presses in the background

# Use the big display
WIDTH = 128
HEIGHT = 64
BORDER = 1

# Use for I2C.
i2c = board.I2C()
oled = adafruit_ssd1306.SSD1306_I2C(WIDTH, HEIGHT, i2c, addr=0x3c)

# Clear display.
oled.fill(0)
oled.show()

drawText("Starting up...")
print ("Starting up...")

triggerfile = "/tmp/oled.txt"
while not done:
	try:
		with open(triggerfile, "r") as f:
			line = f.readline()
			text = line.strip()

		os.remove(triggerfile)
		print("Writing '",text,"'")
		drawText(text)

	except FileNotFoundError:
		pass
		#print('File does not exist')

	time.sleep(0.5)

print("SHUTDOWN!")
#sys.exit()
os.system("sudo poweroff")
