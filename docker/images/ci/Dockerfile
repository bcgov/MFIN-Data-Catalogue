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

WORKDIR /var/www/html

# Reset Cache
RUN php -r 'opcache_reset();'
