#!/bin/sh

echo "$(date "+%Y-%m-%d %H:%M:%S")" > /var/www/docker/crontab/last.log
curl "http://127.0.0.1:20000/crontab" >> /var/www/docker/crontab/last.log
echo "\n$(date "+%Y-%m-%d %H:%M:%S")" >> /var/www/docker/crontab/last.log
