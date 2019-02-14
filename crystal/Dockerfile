FROM alpine:edge

RUN apk add --update --no-cache --force-overwrite \
    openssl openssl-dev crystal shards g++ gc-dev \
    libc-dev libevent-dev libxml2-dev llvm llvm-dev \
    llvm-static make pcre-dev readline-dev \
    yaml-dev zlib-dev zlib llvm-libs

ADD . /src
WORKDIR /src
RUN shards build --production
#RUN crystal run ./support/list-deps.cr -- ./bin/server

EXPOSE 80

CMD ./bin/server