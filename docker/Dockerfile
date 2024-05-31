FROM debian:buster

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
	  openjdk-11-jdk \
	  unzip \
	  rng-tools \
	  python-certbot-apache \
	  mariadb-client \
	  expect \
	  sudo \
	  cron \
	  locales \
	  gnupg2 \
	  netcat \
	  crudini \
	  rsync \
	&& rm -rf /var/cache/apt/archives/* \
	&& rm -rf /var/lib/apt/lists/*

RUN sed -i -e 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen && \
	dpkg-reconfigure --frontend=noninteractive locales && \
	update-locale LANG=en_US.UTF-8

ENV LANG en_US.UTF-8 
ENV LC_ALL en_US.UTF-8

# Add php repo
RUN wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg \
	&& sh -c 'echo "deb https://packages.sury.org/php/ buster main" > /etc/apt/sources.list.d/php.list'

# Install php deps
RUN apt -y update \
	&& apt install -y \
	  php8.0 \
	  php8.0-mcrypt \
	  php8.0-gd \
	  php8.0-curl \
	  php8.0-mysql \
	  php8.0-zip \
	  php8.0-xml \
	  php8.0-intl \
	  php8.0-mbstring \
	  php8.0-xdebug \
	  php8.0-imagick \
	&& rm -rf /var/cache/apt/archives/* \
	&& rm -rf /var/lib/apt/lists/*

# Adding entrypoint file
COPY aspen-discovery/docker/start.sh /start.sh

# Load apache modules
RUN a2enmod rewrite

# Add aspen-discovery
COPY aspen-discovery /usr/local/aspen-discovery

# Delete sites
RUN cd /usr/local/aspen-discovery/sites \
	&& rm -rf *local* \
	&& rm -rf *prod*  \
	&& rm -rf *dev*   \
	&& rm -rf template.windows

CMD ["/start.sh"]
