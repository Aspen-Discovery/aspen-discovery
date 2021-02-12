#!/bin/sh
#create a new user and user group to run Aspen Discovery
adduser aspen
#Add all existing users to the group
for ID in $(cat /etc/passwd | grep /home | cut -d ':' -f1); do (usermod -a -G aspen $ID);done

#Change file permissions so /data is owned by the aspen user
