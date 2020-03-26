/*
DHTXXD.c
2016-02-16
Public Domain
*/

#include <stdio.h>
#include <stdlib.h>

#include <pigpiod_if2.h>

#include "DHTXXD.h"

/*

Code to read the DHTXX temperature/humidity sensors.

*/

/* PRIVATE ---------------------------------------------------------------- */

struct DHTXXD_s
{
   int pi;
   int gpio;
   int model;
   int seconds;
   DHTXXD_CB_t cb;
   int _cb_id;
   pthread_t *_pth;
   int _in_code;
   union
   {
      uint8_t _byte[8];
      uint64_t _code;
   };
   int _bits;
   int _ready;
   int _new_reading;
   DHTXXD_data_t _data;
   uint32_t _last_edge_tick;
   int _ignore_reading;
};

static void _decode_dhtxx(DHTXXD_t *self)
{
/*
      +-------+-------+
      | DHT11 | DHTXX |
      +-------+-------+
Temp C| 0-50  |-40-125|
      +-------+-------+
RH%   | 20-80 | 0-100 |
      +-------+-------+

         0      1      2      3      4
      +------+------+------+------+------+
DHT11 |check-| 0    | temp |  0   | RH%  |
      |sum   |      |      |      |      |
      +------+------+------+------+------+
DHT21 |check-| temp | temp | RH%  | RH%  |
DHT22 |sum   | LSB  | MSB  | LSB  | MSB  |
DHT33 |      |      |      |      |      |
DHT44 |      |      |      |      |      |
      +------+------+------+------+------+
*/
   uint8_t chksum;
   float div;
   float t, h;
   int valid;

   self->_data.timestamp = time_time();

   chksum = (self->_byte[1] + self->_byte[2] +
             self->_byte[3] + self->_byte[4]) & 0xFF;

   valid = 0;

   if (chksum == self->_byte[0])
   {
      if (self->model == DHT11)
      {
         if ((self->_byte[1] == 0) && (self->_byte[3] == 0))
         {
            valid = 1;

            t = self->_byte[2];

            if (t > 60.0) valid = 0;

            h = self->_byte[4];

            if ((h < 10.0) || (h > 90.0)) valid = 0;
         }
      }
      else if (self->model == DHTXX)
      {
         valid = 1;

         h = ((float)((self->_byte[4]<<8) + self->_byte[3]))/10.0;

         if (h > 110.0) valid = 0;

         if (self->_byte[2] & 128) div = -10.0; else div = 10.0;

         t = ((float)(((self->_byte[2]&127)<<8) + self->_byte[1])) / div;

         if ((t < -50.0) || (t > 135.0)) valid = 0;
      }
      else /* AUTO */
      {
         valid = 1;

         /* Try DHTXX first. */

         h = ((float)((self->_byte[4]<<8) + self->_byte[3]))/10.0;

         if (h > 110.0) valid = 0;

         if (self->_byte[2] & 128) div = -10.0; else div = 10.0;

         t = ((float)(((self->_byte[2]&127)<<8) + self->_byte[1])) / div;

         if ((t < -50.0) || (t > 135.0)) valid = 0;

         if (!valid)
         {
            /* If not DHTXX try DHT11. */

            if ((self->_byte[1] == 0) && (self->_byte[3] == 0))
            {
               valid = 1;

               t = self->_byte[2];

               if (t > 60.0) valid = 0;

               h = self->_byte[4];

               if ((h < 10.0) || (h > 90.0)) valid = 0;
            }
         }
      }

      if (valid)
      {
         self->_data.temperature = t;
         self->_data.humidity = h;
         self->_data.status = DHT_GOOD;
      }
      else
      {
         self->_data.status = DHT_BAD_DATA;
      }
   }
   else
   {
      self->_data.status = DHT_BAD_CHECKSUM;
   }

   self->_ready = 1;
   self->_new_reading = 1;

   if (self->cb) (self->cb)(self->_data);
}

static void _cb(
   int pi, unsigned gpio, unsigned level, uint32_t tick, void *user)
{

   DHTXXD_t *self=user;
   int edge_len;

   edge_len = tick - self->_last_edge_tick;
   self->_last_edge_tick = tick;

   if (edge_len > 10000)
   {
      self->_in_code = 1;
      self->_bits = -2;
      self->_code = 0;
   }
   else if (self->_in_code)
   {
      self->_bits++;
      if (self->_bits >= 1)
      {
         self->_code <<= 1;

         if ((edge_len >= 60) && (edge_len <= 100))
         {
            /* 0 bit */
         }
         else if ((edge_len > 100) && (edge_len <= 150))
         {
            /* 1 bit */
            self->_code += 1;
         }
         else
         {
            /* invalid bit */
            self->_in_code = 0;
         }

         if (self->_in_code)
         {
            if (self->_bits == 40)
            {
               if (!self->_ignore_reading) _decode_dhtxx(self);
            }
         }
      }
   }
}

static void _trigger(DHTXXD_t *self)
{
   gpio_write(self->pi, self->gpio, 0);
   if (self->model != DHTXX) time_sleep(0.018); else time_sleep(0.001);
   set_mode(self->pi, self->gpio, PI_INPUT);
}

static void *pthTriggerThread(void *x)
{
   DHTXXD_t *self=x;
   float seconds;

   seconds = self->seconds;

   while (1)
   {
      if (seconds > 0.0)
      {
         if (seconds >= 30.0)
         {
            time_sleep(seconds - 4.0);
            self->_ignore_reading = 1;
            _trigger(self);
            time_sleep(4.0);
            self->_ignore_reading = 0;
         }
         else time_sleep(seconds);

         DHTXXD_manual_read(self);
      }
      else time_sleep(1);
   }
   return NULL;
}

/* PUBLIC ----------------------------------------------------------------- */

DHTXXD_t *DHTXXD(int pi, int gpio, int model, DHTXXD_CB_t cb_func)
{
   DHTXXD_t *self;

   self = malloc(sizeof(DHTXXD_t));

   if (!self) return NULL;

   self->pi = pi;
   self->gpio = gpio;
   self->model = model;
   self->seconds = 0;
   self->cb = cb_func;

   self->_data.pi = pi;
   self->_data.gpio = gpio;
   self->_data.status = 0;
   self->_data.temperature = 0.0;
   self->_data.humidity = 0.0;

   self->_ignore_reading = 0;

   self->_pth = NULL;

   self->_in_code = 0;

   self->_ready = 0;
   self->_new_reading = 0;

   set_mode(pi, gpio, PI_INPUT);

   self->_last_edge_tick = get_current_tick(pi) - 10000;

   self->_cb_id = callback_ex(pi, gpio, RISING_EDGE, _cb, self);

   return self;
}

void DHTXXD_cancel(DHTXXD_t *self)
{
   if (self)
   {
      if (self->_pth)
      {
         stop_thread(self->_pth);
         self->_pth = NULL;
      }

      if (self->_cb_id >= 0)
      {
         callback_cancel(self->_cb_id);
         self->_cb_id = -1;
      }
      free(self);
   }
}
int DHTXXD_ready(DHTXXD_t *self)
{
   /*
   Returns True if a new unread code is ready.
   */
   return self->_ready;
}

DHTXXD_data_t DHTXXD_data(DHTXXD_t *self)
{
   /*
   Returns the last reading.
   */
   self->_ready = 0;
   return self->_data;
}

void DHTXXD_manual_read(DHTXXD_t *self)
{
   int i;
   double timestamp;

   self->_new_reading = 0;
   _trigger(self);
   timestamp = time_time();

   /* timeout if no new reading */

   for (i=0; i<5; i++) /* 0.25 seconds */
   {
      time_sleep(0.05);
      if (self->_new_reading) break;
   }

   if (!self->_new_reading)
   {
      self->_data.timestamp = timestamp;
      self->_data.status = DHT_TIMEOUT;
      self->_ready = 1;

      if (self->cb) (self->cb)(self->_data);
   }
}

void DHTXXD_auto_read(DHTXXD_t *self, float seconds)
{
   if (seconds != self->seconds)
   {
      /* Delete any existing timer thread. */
      if (self->_pth != NULL)
      {
         stop_thread(self->_pth);
         self->_pth = NULL;
      }
      self->seconds = seconds;
   }

   if (seconds > 0.0) self->_pth = start_thread(pthTriggerThread, self);
}

