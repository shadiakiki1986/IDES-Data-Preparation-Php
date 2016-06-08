#!/bin/bash

echo `date`
echo update libraries if available
composer update --quiet
echo update public files if available
bash download.sh
echo start apache server
/usr/sbin/apache2ctl -D FOREGROUND
