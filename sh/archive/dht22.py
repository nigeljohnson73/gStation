#!/usr/bin/python3

import sys, getopt
import Adafruit_DHT

DHT_SENSOR = Adafruit_DHT.DHT22
DHT_PIN = 4


def main(argv):
   inputfile = ''
   outputfile = ''
   try:
      opts, args = getopt.getopt(argv, "hi:o:", ["ifile=", "ofile="])
   except getopt.GetoptError:
      print(sys.argv[0], "-i <inputfile> -o <outputfile>")
      sys.exit(2)
   for opt, arg in opts:
      if opt == '-h':
         print(sys.argv[0], "-i <inputfile> -o <outputfile>")
         sys.exit()
      elif opt in ("-i", "--ifile"):
         inputfile = arg
      elif opt in ("-o", "--ofile"):
         outputfile = arg

   print("Input file is: '", inputfile, "'")
   print("Output file is: '", outputfile, "'")

   humidity, temperature = Adafruit_DHT.read_retry(DHT_SENSOR, DHT_PIN)

   if humidity is not None and temperature is not None:
       print("T:{0:0.1f}|H:{1:0.1f}".format(temperature, humidity))
   else:
       print("T:999|H:999")


if __name__ == "__main__":
   main(sys.argv[1:])
