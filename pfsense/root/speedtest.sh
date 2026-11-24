#!/bin/sh
/usr/local/bin/speedtest --accept-license --accept-gdpr --format=json >> /var/log/speedtest.log 2>&1
