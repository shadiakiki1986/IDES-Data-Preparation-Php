#!/bin/bash

# chown of backup folder so that apache can put files there
mkdir -p ws/bkp ws/downloads && \
    chown www-data:www-data ws/bkp ws/downloads -R

# LAUNCH
/usr/sbin/apache2ctl -D FOREGROUND
