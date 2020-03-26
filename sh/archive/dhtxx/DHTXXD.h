/*
DHTXXD.h
2015-11-15
Public Domain
*/

#ifndef DHTXXD_H
#define DHTXXD_H

struct DHTXXD_s;

typedef struct DHTXXD_s DHTXXD_t;

#define DHTAUTO 0
#define DHT11   1
#define DHTXX   2

#define DHT_GOOD         0
#define DHT_BAD_CHECKSUM 1
#define DHT_BAD_DATA     2
#define DHT_TIMEOUT      3

typedef struct
{
   int pi;
   int gpio;
   int status;
   float temperature;
   float humidity;
   double timestamp;
} DHTXXD_data_t;

typedef void (*DHTXXD_CB_t)(DHTXXD_data_t);

/*
DHTXXD starts a DHTXX sensor on Pi pi with GPIO gpio.

The model may be auto detected (DHTAUTO), or specified as a
DHT11 (DHT11), or DHT21/22/33/44 (DHTXX).

If cb_func is not null it will be called at each new reading
whether the received data is valid or not.  The callback
receives a DHTXXD_data_t object.

If cb_func is null then the DHTXXD_ready function should be
called to check for new data which may then be retrieved by
a call to DHTXXD_data.

A single reading may be triggered with DHTXXD_manual_read.

A reading may be triggered at regular intervals using
DHTXXD_auto_read.  If the auto trigger interval is 30
seconds or greater two readings will be taken and only
the second will be returned.  I would not read the
DHT22 more than once every 3 seconds.  The DHT11 can
safely be read once a second.  I don't know about the
other models.

At program end the DHTXX sensor should be cancelled using
DHTXXD_cancel.  This releases system resources.
*/

DHTXXD_t     *DHTXXD             (int pi,
                                  int gpio,
                                  int model,
                                  DHTXXD_CB_t cb_func);

void          DHTXXD_cancel      (DHTXXD_t *self);

int           DHTXXD_ready       (DHTXXD_t *self);

DHTXXD_data_t DHTXXD_data        (DHTXXD_t *self);

void          DHTXXD_manual_read (DHTXXD_t *self);

void          DHTXXD_auto_read   (DHTXXD_t *self, float seconds);

#endif

