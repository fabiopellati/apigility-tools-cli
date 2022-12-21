FROM composer:2 AS composer
FROM php:7.3-cli

RUN apt-get update  && apt-get install -y ntpdate\
 && apt-get install -y git curl libmcrypt4 libicu-dev zlib1g-dev freetds-dev libxml2-dev libzip-dev telnet libpng-dev libpq-dev\
 && apt-get clean -y make clean \
&& apt-get install -y \
    ssh \
    libssh2-1 \
    libssh2-1-dev \
    wget \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*



RUN wget https://pecl.php.net/get/ssh2 -O /tmp/ssh2-1.2.tgz \
    && pear install /tmp/ssh2-1.2.tgz \
    && docker-php-ext-enable ssh2


RUN docker-php-ext-install fileinfo \
 && docker-php-ext-install gd \
 && docker-php-ext-install zip \
 && docker-php-ext-install pdo \
 && docker-php-ext-install pdo_mysql \
  && docker-php-ext-install pdo_pgsql \
&& docker-php-ext-install intl \
 && docker-php-ext-install xml \
 && docker-php-ext-install  soap \
&& ln -s /usr/lib/x86_64-linux-gnu/libsybdb.a /usr/lib/ \
&& docker-php-ext-install pdo_dblib \
&& pecl install -o -f redis \
&& pecl install -o -f mongodb \
&& docker-php-ext-enable redis \
&& docker-php-ext-enable mongodb \
&& rm -rf /tmp/pear

WORKDIR /usr/src/app

COPY --from=composer /usr/bin/composer /usr/bin/composer



WORKDIR /usr/src/app




