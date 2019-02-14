FROM php:7.3.1-cli-alpine

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN apk --no-cache add php7-json php7-mbstring php7-zip php7-pear php7-dev gcc make curl g++ redis libzip-dev unzip

ENV REDIS_VERSION 4.2.0
RUN curl -L -o /tmp/redis.tar.gz https://github.com/phpredis/phpredis/archive/$REDIS_VERSION.tar.gz \
    && tar xfz /tmp/redis.tar.gz \
    && rm -r /tmp/redis.tar.gz \
    && mkdir -p /usr/src/php/ext \
    && mv phpredis-* /usr/src/php/ext/redis

RUN docker-php-ext-install zip
RUN docker-php-ext-install redis

# install swoole
#TIP: it always get last stable version of swoole coroutine.
RUN cd /root && \
    curl -o /tmp/swoole-releases https://github.com/swoole/swoole-src/releases -L && \
    cat /tmp/swoole-releases | grep 'href=".*archive.*.tar.gz"' | head -1 | \
    awk -F '"' ' {print "curl -o /tmp/swoole.tar.gz https://github.com"$2" -L" > "/tmp/swoole.download"}' && \
    sh /tmp/swoole.download && \
    tar zxvf /tmp/swoole.tar.gz && cd swoole-src* && \
    phpize && \
    ./configure \
#    --enable-coroutine \
#    --enable-openssl \
#    --enable-http2 \
#    --enable-async-redis \
#    --enable-mysqlnd \
    && make && make install && \
    docker-php-ext-enable swoole && \
    echo "swoole.fast_serialize=On" >> /usr/local/etc/php/conf.d/docker-php-ext-swoole-serialize.ini && \
    rm -rf /tmp/*

# Copy configuration
#COPY config/redis.ini /usr/local/etc/php/conf.d/

# Add application
RUN mkdir -p /var/www/html
WORKDIR /var/www/html
COPY ./application /var/www/html

#RUN mkdir -p /tmp/data
#COPY ./data/data.zip /tmp/data

#RUN ["chmod", "+x", "/var/www/html/start.sh"]

EXPOSE 80

ENTRYPOINT redis-server --save "" --daemonize yes && unzip /tmp/data/data.zip -d /var/www/html/data && php /var/www/html/server.php
