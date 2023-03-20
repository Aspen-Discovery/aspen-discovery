# Install the same php8 pakcages as the centos script
apt-get install -y php8.0 php8.0-common php8.0-cli
# Disable current php and enable php 8
a2dismod 'php*'
a2enmod php8.0
apt-get install -y "php8.0" "php8.0-mcrypt" "php8.0-gd" "php8.0-imagick" "php8.0-curl" "php8.0-mysql" "php8.0-zip" "php8.0-xml" "php8.0-intl" "php8.0-mbstring" "php8.0-soap" "php8.0-pgsql" "php8.0-ssh2"