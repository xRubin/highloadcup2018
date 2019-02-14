FROM php:7.3.1-cli-alpine

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN apk --no-cache add php7-json php7-mbstring php7-zip php7-pear php7-dev gcc make curl g++ redis libzip-dev unzip shadow

# Composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && composer self-update --clean-backups

ENV REDIS_VERSION 4.2.0
RUN curl -L -o /tmp/redis.tar.gz https://github.com/phpredis/phpredis/archive/$REDIS_VERSION.tar.gz \
    && tar xfz /tmp/redis.tar.gz \
    && rm -r /tmp/redis.tar.gz \
    && mkdir -p /usr/src/php/ext \
    && mv phpredis-* /usr/src/php/ext/redis

RUN docker-php-ext-install zip
RUN docker-php-ext-install redis

# Swoole extension
ENV SWOOLE_VERSION 4.2.12
RUN wget https://github.com/swoole/swoole-src/archive/v${SWOOLE_VERSION}.tar.gz -O swoole.tar.gz \
    && mkdir -p swoole \
    && tar -xf swoole.tar.gz -C swoole --strip-components=1 \
    && rm swoole.tar.gz \
    && ( \
    cd swoole \
    && phpize \
    && ./configure --enable-async-redis \
    && make -j$(nproc) \
    && make install \
    ) \
    && rm -r swoole \
    && docker-php-ext-enable swoole

# Add application
RUN mkdir -p /var/www/html
WORKDIR /var/www/html
#COPY . /var/www/html


COPY ./composer.json ./App ./easyswoole ./EasySwooleEvent.php ./produce.php ./upload.php ./check.php ./
RUN composer install --no-autoloader
COPY . ./
RUN composer dump-autoload

EXPOSE 80

ENTRYPOINT redis-server --save "" --appendonly no --daemonize yes && \
    unzip -q /tmp/data/data.zip -d /var/www/html/data && \
    cd /var/www/html/ && \
    composer dumpautoload && \
    php upload.php && \
    php easyswoole start produce
