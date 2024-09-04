FROM debian:bookworm

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
	  software-properties-common \
	  default-jdk \
	  openjdk-17-jdk \
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
	&& rm -rf /var/cache/apt/archives/* \
	&& rm -rf /var/lib/apt/lists/* \
	&& rm -rf /etc/cron.*/*

RUN sed -i -e 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen && \
	dpkg-reconfigure --frontend=noninteractive locales && \
	update-locale LANG=en_US.UTF-8

ENV LANG=en_US.UTF-8
ENV LC_ALL=en_US.UTF-8

# Install php deps
RUN apt -y update \
	&& apt install -y \
	  php8.2 \
	  php8.2-mcrypt \
	  php8.2-gd \
	  php8.2-curl \
	  php8.2-mysql \
	  php8.2-zip \
	  php8.2-xml \
	  php8.2-intl \
	  php8.2-mbstring \
	  php8.2-xdebug \
	  php8.2-imagick \
	&& rm -rf /var/cache/apt/archives/* \
	&& rm -rf /var/lib/apt/lists/*

# Adding entrypoint and init scripts
COPY docker/files/scripts/entrypoint.sh  /entrypoint.sh
COPY docker/files/scripts/start.sh       /start.sh
COPY docker/files/scripts/cron.sh        /cron.sh

# Load apache modules
RUN a2enmod rewrite
RUN a2dissite 000-default

# Add aspen-discovery
COPY . /usr/local/aspen-discovery

# Delete sites
RUN cd /usr/local/aspen-discovery/sites \
	&& rm -rf *local* \
	&& rm -rf *prod*  \
	&& rm -rf *dev*   \
	&& rm -rf template.windows

ENTRYPOINT ["/entrypoint.sh"]
