# Install PHP 8
yum-config-manager --disable 'remi-php*'
yum-config-manager --enable remi-php80
yum -y install php php-cli php-common