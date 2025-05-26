ARG BASE_IMAGE
FROM $BASE_IMAGE

COPY php.ini /usr/local/etc/php/php.ini

## START CI

WORKDIR /var/www

RUN ln -s /var/www/vendor/bin/behat /usr/local/bin/behat; \
    ln -s /var/www/vendor/bin/phpcs /usr/local/bin/phpcs; \
    ln -s /var/www/vendor/bin/phpmd /usr/local/bin/phpmd; \
    ln -s /var/www/vendor/bin/phpunit /usr/local/bin/phpunit; \
    ln -s /var/www/vendor/bin/drupal /usr/local/bin/drupal

RUN phpcs --config-set installed_paths /var/www/vendor/drupal/coder/coder_sniffer

## END CI

# XDebug
# RUN git clone https://github.com/xdebug/xdebug.git /tmp/xdebug; \
#     mkdir -p /usr/src/php/ext; \
#     mv /tmp/xdebug /usr/src/php/ext/; \
#     docker-php-ext-install xdebug

# RUN sed -i '1 a xdebug.remote_autostart=true' /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
#     sed -i '1 a xdebug.remote_mode=req' /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
#     sed -i '1 a xdebug.remote_handler=dbgp' /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
#     sed -i '1 a xdebug.remote_connect_back=1 ' /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
#     sed -i '1 a xdebug.remote_port=9000' /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
#     sed -i '1 a xdebug.remote_host=127.0.0.1' /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
#     sed -i '1 a xdebug.remote_enable=1' /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
#     sed -i '1 a xdebug.idekey=phpstorm' /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Mac hack to get permissions to work.
# Set user 1000 and group staff to www-data.
# https://github.com/boot2docker/boot2docker/issues/581#issuecomment-114804894
RUN echo "@community http://dl-cdn.alpinelinux.org/alpine/edge/community" >> /etc/apk/repositories
RUN apk add --update --no-cache shadow@community; \
    addgroup staff; \
    usermod -u 1000 www-data; \
    usermod -G staff www-data

WORKDIR /var/www/html

# Reset Cache
RUN php -r 'opcache_reset();'
