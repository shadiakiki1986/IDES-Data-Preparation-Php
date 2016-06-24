#!/bin/bash

# chown of backup folder so that apache can put files there
mkdir -p cache/bkp cache/downloads && \
    chown www-data:www-data cache/bkp cache/downloads -R

# LAUNCH
apache2-foreground # /usr/sbin/apache2ctl -D FOREGROUND

