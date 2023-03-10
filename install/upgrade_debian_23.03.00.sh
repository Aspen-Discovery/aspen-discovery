# Install the same php8 pakcages as the centos script
apt-get install -y php8.0 php8.0-common php8.0-cli
# Disable current php and enable php 8
a2dismod 'php*'
a2enmod php8.0
