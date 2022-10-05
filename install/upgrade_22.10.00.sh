#!/bin/sh
if [ -z "$1" ]
  then
    echo "Please provide the server name to update as the first argument."
    exit 1
fi

php /usr/local/aspen-discovery/install/updateCron_22_10.php $1


#upgrade openssh to 9.0
yum groupinstall "Development Tools" -y
yum install zlib-devel openssl-devel -y

cp /etc/ssh/sshd_config  /etc/ssh/sshd_config

wget -c  https://cdn.openbsd.org/pub/OpenBSD/OpenSSH/portable/openssh-9.0p1.tar.gz

tar -xzf  openssh-9.0p1.tar.gz

cd openssh-9.0p1/

yum install pam-devel libselinux-devel -y

./configure  --with-pam --with-selinux --with-privsep-path=/var/lib/sshd/ --sysconfdir=/etc/ssh

make
make install


# Disable Apache serversignature
cp install/httpd.conf /etc/httpd/conf/httpd.conf
sudo systemctl restart httpd.service
