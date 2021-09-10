FROM php:8.2-cli

RUN apt-get update && \
    apt-get install git unzip curl -y

RUN curl https://getcomposer.org/download/2.1.14/composer.phar -o /usr/local/bin/composer
RUN php -r "if (hash_file('sha256', '/usr/local/bin/composer') === 'd44a904520f9aaa766e8b4b05d2d9a766ad9a6f03fa1a48518224aad703061a4') { exit(0); } else { exit(1); }"
RUN chmod +x /usr/local/bin/composer

RUN docker-php-ext-install sockets \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

ARG UID=1000

RUN groupadd -g $UID -o -r user \
    && useradd -d /user -g $UID -o -r -u $UID user \
    && mkdir /user \
    && chown user: /user

USER user