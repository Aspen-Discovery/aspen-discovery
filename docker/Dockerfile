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
	  man \
	&& rm -rf /var/cache/apt/archives/* \
	&& rm -rf /var/lib/apt/lists/* \
	&& rm -rf /etc/cron.*/*

RUN sed -i -e 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen && \
	dpkg-reconfigure --frontend=noninteractive locales && \
	update-locale LANG=en_US.UTF-8

ENV LANG=en_US.UTF-8
ENV LC_ALL=en_US.UTF-8

# Add sury's repo
RUN wget -O- -q https://packages.sury.org/php/apt.gpg \
      | gpg --dearmor \
      | tee /usr/share/keyrings/sury.gpg >/dev/null \
   && echo "deb [signed-by=/usr/share/keyrings/sury.gpg] https://packages.sury.org/php/ bookworm main" > /etc/apt/sources.list.d/sury.list

# Install php deps
RUN apt -y update \
	&& apt install -y \
	  php8.4 \
	  php8.4-mcrypt \
	  php8.4-gd \
	  php8.4-imagick \
	  php8.4-curl \
	  php8.4-mysql \
	  php8.4-zip \
	  php8.4-xml \
	  php8.4-intl \
	  php8.4-mbstring \
	  php8.4-soap \
	  php8.4-pgsql \
	  php8.4-ssh2 \
	  php8.4-ldap \
	  php8.4-xdebug \
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
