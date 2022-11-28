# Disable apache server signature
echo -e "ServerSignature Off \nServerTokens Prod" >> /etc/httpd/conf/httpd.conf

# configure mod evasive
yum install httpd-devel
yum install epel-release
yum install mod_evasive
cp install/mod_evasive.conf /etc/httpd/conf.d/mod_evasive.conf
service httpd restart

#configure mod security
yum install mod_security -y
service httpd restart
