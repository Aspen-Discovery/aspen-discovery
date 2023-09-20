#!/bin/sh
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi

yum -y install clamav-server clamav-data clamav-update clamav-filesystem clamav clamav-scanner-systemd clamav-devel clamav-lib clamav-server-systemd
sed -i -e "s/^Example/#Example/" /etc/clamd.d/scan.conf
sed -i -e "s/^#LocalSocket\s/LocalSocket /" /etc/clamd.d/scan.conf
#sed -i -e "s/^#LogFile\s/LogFile /" /etc/clamd.d/scan.conf
#sed -i -e "s/^#LogFileMaxSize\s/LogFileMaxSize /" /etc/clamd.d/scan.conf
freshclam
systemctl start clamd@scan
systemctl enable clamd@scan

touch /var/log/aspen-discovery/clam_av.log
chown root:aspen_apache /var/log/aspen-discovery/clam_av.log

yum -y install php-ldap

php /usr/local/aspen-discovery/install/updateCron_23_09.php $1

#sed -i -e '$aExcludePath ^/var/lib/mysql/*' '/etc/clamd.d/scan.conf'
#sed -i -e '$aExcludePath ^/data/aspen-discovery/$1/solr7/*' '/etc/clamd.d/scan.conf'
#sed -i -e '$aExcludePath ^/data/aspen-discovery/$1/covers/small/*' '/etc/clamd.d/scan.conf'
#sed -i -e '$aExcludePath ^/data/aspen-discovery/$1/covers/medium/*' '/etc/clamd.d/scan.conf'
#sed -i -e '$aExcludePath ^/data/aspen-discovery/$1/covers/large/*' '/etc/clamd.d/scan.conf'
#sed -i -e '$aExcludePath ^/var/log/aspen-discovery/$1/*' '/etc/clamd.d/scan.conf'
#sed -i -e '$aExcludePath ^/sys/*' '/etc/clamd.d/scan.conf'