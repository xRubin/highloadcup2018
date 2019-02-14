#FROM alpine:3.8
#FROM php:7.2-fpm-alpine
#7.3.1-alpine3.8, 7.3-alpine3.8, 7-alpine3.8, alpine3.8, 7.3.1-cli-alpine, 7.3-cli-alpine, 7-cli-alpine, cli-alpine, 7.3.1-alpine, 7.3-alpine, 7-alpine, alpine (7.3/alpine3.8/cli/Dockerfile)
FROM php:7.3.1-cli-alpine

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

#RUN apk --no-cache add php7 php7-fpm php7-json php7-mbstring php7-zip php7-pear php7-dev gcc make curl
RUN apk --no-cache add php7-json php7-pear php7-dev gcc make curl g++ unzip
# openssl-dev

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

EXPOSE 80

CMD unzip /tmp/data/data.zip -d /var/www/html/data && \
    php /var/www/html/empty.php