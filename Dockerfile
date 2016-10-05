FROM alpine:edge
MAINTAINER scher200

# Install packages
RUN apk upgrade -U 
RUN apk --no-cache --update --repository=http://dl-4.alpinelinux.org/alpine/edge/testing add \
    openssl \
    php7 \
    php7-fpm \
    php7-json \
    php7-phar \
    php7-iconv \
    php7-openssl \
    php7-opcache \
    php7-ctype \
    php7-dom \
    php7-mbstring \
    php7-curl \
    php7-pdo \
    wget \ 
    curl \
    nginx \
    bash \
    openssl \
    ca-certificates \
    supervisor

RUN rm -fr /var/cache/apk/*

# install dumb-init
RUN wget -O /usr/local/bin/dumb-init https://github.com/Yelp/dumb-init/releases/download/v1.1.1/dumb-init_1.1.1_amd64
RUN chmod +x /usr/local/bin/dumb-init

# link php and php7 command
RUN ln -sf /usr/bin/php7 /usr/bin/php

# Set up Sabre DAV
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=bin --filename=composer
WORKDIR /sabre
RUN composer require sabre/dav ~2.0.5

# insert the root folder and overwrite the configuration files
COPY /rootfs /

# garantee sabre data folder and user rights
RUN mkdir -p /sabre/data && \
    chmod a+rwx /sabre/data && \
    chown -R xfs:xfs /sabre/ && \
    rm -Rf /sabre/files/html


EXPOSE 80

ENTRYPOINT ["/usr/local/bin/dumb-init"]

CMD ["/shell/start.sh"]


