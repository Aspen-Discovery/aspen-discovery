FROM debian:trixie

# Install deps
RUN apt -y update \
	&& apt -y install \
	  apt-utils \
	  gettext-base \
	  wget \
	  apache2 \
	  apt-transport-https \
	  lsb-release \
	  ca-certificates \
	  curl \
	  git \
	  vim \
	  bind9 \
	  bind9utils \
	  openjdk-25-jdk \
	  unzip \
	  rng-tools \
	  python3-certbot-apache \
	  mariadb-client \
	  expect \
	  sudo \
	  cron \
	  locales \
	  gnupg2 \
	  netcat-openbsd \
	  inetutils-ping \
	  rsync \
	  man \
	&& rm -rf /var/cache/apt/archives/* \
	&& rm -rf /var/lib/apt/lists/* \
	&& rm -rf /etc/cron.*/*

RUN sed -i -e 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen && \
	dpkg-reconfigure --frontend=noninteractive locales && \
	update-locale LANG=en_US.UTF-8

ENV LANG=en_US.UTF-8
ENV LC_ALL=en_US.UTF-8

# Install php deps (trixie ships PHP 8.4 as the default version natively, no third-party repo needed)
RUN apt -y update \
	&& apt install -y \
	  php \
	  php-fpm \
	  php-mcrypt \
	  php-gd \
	  php-imagick \
	  php-curl \
	  php-zip \
	  php-xml \
	  php-intl \
	  php-mbstring \
	  php-soap \
	  php-pgsql \
	  php-ssh2 \
	  php-ldap \
	  php-mysql \
	&& rm -rf /var/cache/apt/archives/* \
	&& rm -rf /var/lib/apt/lists/*


# Enable required Apache modules
RUN a2enmod proxy_fcgi setenvif rewrite
RUN a2dissite 000-default

# Adding entrypoint and init scripts
COPY docker/files/scripts/entrypoint.sh  /entrypoint.sh
COPY docker/files/scripts/start.sh       /start.sh
COPY docker/files/scripts/cron.sh        /cron.sh
COPY docker/files/scripts/apache.sh      /apache.sh

# Add aspen-discovery
COPY . /usr/local/aspen-discovery

# Delete sites
RUN cd /usr/local/aspen-discovery/sites \
	&& rm -rf *local* \
	&& rm -rf *prod*  \
	&& rm -rf *dev*   \
	&& rm -rf template.windows


ENTRYPOINT ["/entrypoint.sh"]
