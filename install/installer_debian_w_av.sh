#!/bin/sh

./installer_debian.sh

apt-get -y install clamav clamav-daemon

touch /var/log/aspen-discovery/clam_av.log
chown root:aspen_apache /var/log/aspen-discovery/clam_av.log