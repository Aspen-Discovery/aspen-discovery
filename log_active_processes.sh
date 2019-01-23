#!/bin/bash

mkdir -p "/var/log/ps_logs"

while true; do
    ps aux | grep ^[^[]*$ > "/var/log/ps_logs/ps_$(date +%Y-%m-%d_%H:%M:%S).log"
    sleep 60 # Logging interval in seconds.
done