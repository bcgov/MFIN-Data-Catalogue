# https://github.com/docker-library/drupal/blob/master/10.2/php8.2/fpm-alpine3.19/Dockerfile
FROM drupal:10.2-php8.2-fpm-alpine

# This Drupal-Alpine base image contains a full copy of Drupal (and its vendor folder).
# We don't actually use this at all, and build our own version of Drupal, below.
# So, to recover the 135MB that this takes up (in our pods, and in Artifactory images),
# we delete it now:
#   /opt/drupal is the drupal install
#   /var/www/html is a symlink to it
RUN rm -rf /opt/drupal /var/www/html

ARG SSH_PRIVATE_KEY
ARG GIT_USERNAME
ARG GIT_PASSWORD
ARG GITHUB_SHA

RUN apk --update add fcgi \
    && curl -o /usr/local/bin/php-fpm-healthcheck https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck \
    && chmod +x /usr/local/bin/php-fpm-healthcheck
COPY docker/conf/php-fpm/status.conf /usr/local/etc/php-fpm.d/

HEALTHCHECK --interval=5s --timeout=10s --start-period=5s --retries=3 CMD [ "php-fpm-healthcheck" ]

# Install additional extensions
RUN apk --update add --no-cache bash \
                                git \
                                gzip \
                                mysql-client \
                                patch \
                                postgresql-client \
                                rsync \
                                ssmtp \
                                zlib-dev

                                # Resolve https://github.com/composer/composer/issues/11913
RUN apk add --update \
    && apk add --no-cache curl --repository=https://dl-cdn.alpinelinux.org/alpine/edge/community \
    && rm -rf /var/cache/apk/*

COPY docker/conf/ssmtp.conf /etc/ssmtp/ssmtp.conf
RUN echo "hostname=drupalwxt.github.io" >> /etc/ssmtp/ssmtp.conf
RUN echo 'sendmail_path = "/usr/sbin/ssmtp -t"' > /usr/local/etc/php/conf.d/mail.ini
COPY docker/conf/php.ini /usr/local/etc/php/php.ini

# Install additional php extensions
RUN apk add --update --no-cache autoconf \
                                icu \
                                icu-libs \
                                libzip-dev \
                                gcc \
                                g++ \
                                make \
                                libffi-dev \
                                openssl-dev; \
    \
    apk add --no-cache --virtual .build-deps icu-dev; \
    \
    docker-php-ext-configure zip \
        --with-zlib-dir=/usr; \
    \
    docker-php-ext-install -j "$(nproc)" \
        bcmath \
        intl \
        zip; \
    \
    apk del .build-deps

COPY docker/certs/BaltimoreCyberTrustRoot.crt.pem /etc/ssl/mysql/BaltimoreCyberTrustRoot.crt.pem

# Redis
ENV PHPREDIS_VERSION 5.3.7
RUN mkdir -p /usr/src/php/ext/redis \
    && curl -L https://github.com/phpredis/phpredis/archive/$PHPREDIS_VERSION.tar.gz | tar xvz -C /usr/src/php/ext/redis --strip 1 \
    && echo 'redis' >> /usr/src/php-available-exts \
    && docker-php-ext-install redis

# Composer recommended settings
ENV COMPOSER_ALLOW_SUPERUSER 1
ARG COMPOSER_VERSION
ENV COMPOSER_MEMORY_LIMIT -1
ENV COMPOSER_EXIT_ON_PATCH_FAILURE 1

# Check Composer
RUN curl -o /tmp/composer-setup.php https://getcomposer.org/installer; \
    curl -o /tmp/composer-setup.sig https://composer.github.io/installer.sig; \
    php -r "if (hash('SHA384', file_get_contents('/tmp/composer-setup.php')) !== trim(file_get_contents('/tmp/composer-setup.sig'))) { unlink('/tmp/composer-setup.php'); echo 'Invalid installer' . PHP_EOL; exit(1); }"

# Install Composer
RUN php /tmp/composer-setup.php --no-ansi \
                                --install-dir=/usr/local/bin \
                                --filename=composer \
                                --version=${COMPOSER_VERSION}; \
    rm -rf /tmp/composer-setup.php

# Install Drupal WxT
RUN rm -f /var/www/composer.lock; \
    rm -rf /root/.composer
RUN rm -rf /var/www/*
COPY scripts/ /var/www/scripts/
COPY composer.json composer.lock /var/www/
# Copy possible custom modules and custom themes
COPY html/modules/custom/ /var/www/html/modules/custom/
COPY html/themes/custom/ /var/www/html/themes/custom/
# Copy possible config/sync and other config
COPY config/ /var/www/config/
# Copy possible load.environment.php
COPY load.environment.php /var/www/

# Copy patches directory for local patches.
# Create the directory if it doesn't exist in the build context.
RUN mkdir -p composer.patches
COPY composer.patches/ /var/www/composer.patches/

WORKDIR /var/www

RUN apk --update --no-cache add git openssh-client; \
    mkdir -p /root/.ssh; echo $SSH_PRIVATE_KEY | base64 -d > /root/.ssh/id_rsa; \
    chmod 700 /root/.ssh; chmod 600 /root/.ssh/id_rsa; \
    ssh-keyscan github.com > /root/.ssh/known_hosts
RUN composer install --no-dev \
                     --prefer-dist \
                     --ignore-platform-reqs \
                     --no-interaction
RUN rm -rf /root/.ssh && \
    apk del openssh-client

# Permissions
WORKDIR /var/www/html
RUN chown -R www-data:www-data sites/default

# See: https://github.com/docker/docker/issues/9299
RUN echo "export TERM=xterm" >> ~/.bashrc

# Drush
RUN ln -s /var/www/vendor/drush/drush/drush /usr/local/bin/drush

# Reset Cache
RUN php -r 'opcache_reset();'

# Record which git commit is loaded into the pod.
RUN echo "$GITHUB_SHA" > /var/www/build-hash.txt

# Load secrets automatically upon bash-login.
#   Note that the secrets are created from gitops, using
#   the "vault.hashicorp.com/agent-inject-template-secrets.env" annotation.
RUN cat <<'END_BASHRC_LINES' >> /etc/bash/bashrc
  source /vault/secrets/secrets.env
  echo 'Loaded secrets into environment variables.'
END_BASHRC_LINES

# Set the bash prompt colours and text.
# "\[\033[38;2;0;0;0m\]"   means set FG to RGB black
# "\[\033[48;2;0;255;0m\]" means set BG to RGB green
# \w is current directory
# \$ is prompt ($, or might be # for root)
RUN cat <<'END_BASHRC_LINES' >> /etc/bash/bashrc
  case "$ENVIRONMENT_NAME" in
    "dev")   ENVIRONMENT_INDICATOR='\[\033[38;2;0;0;0m\]\[\033[48;2;160;255;160m\]Dev'    ;; # Black on green.
    "test")  ENVIRONMENT_INDICATOR='\[\033[38;2;0;0;0m\]\[\033[48;2;230;230;96m\]Test'    ;; # Black on yellow.
    "prod")  ENVIRONMENT_INDICATOR='\[\033[38;2;255;255;255m\]\[\033[48;2;235;0;0m\]PROD' ;; # White on red.
    *) echo "Bad environment name, '$ENV_NAME'." ;;
  esac

  # HSL (Hue/Sat/Lum) versions of these colours. 
  #   - Each project has a unique hue.
  #   - "Name colour" has Sat=50%, Lum=50%
  #   - "Dir colour" has Sat=100%, Lum=93%
  # The Bash prompt only supports RGB format, so they have been converted from HSL to RGB.
  #
# PROJECT_NAME='intra'; PROJECT_NAME_COLOUR="\[\033[38;2;191;191;064m\]"; PROJECT_DIR_COLOUR="\[\033[48;2;250;250;220m\]"  # Hue:60
  PROJECT_NAME='fdc';   PROJECT_NAME_COLOUR="\[\033[38;2;064;191;191m\]"; PROJECT_DIR_COLOUR="\[\033[48;2;220;250;250m\]"  # Hue:180
# PROJECT_NAME='cas';   PROJECT_NAME_COLOUR="\[\033[38;2;064;064;191m\]"; PROJECT_DIR_COLOUR="\[\033[48;2;220;220;250m\]"  # Hue:240
# PROJECT_NAME='ess';   PROJECT_NAME_COLOUR="\[\033[38;2;191;064;191m\]"; PROJECT_DIR_COLOUR="\[\033[48;2;250;220;250m\]"  # Hue:300

  RESET_COLOUR='\[\033[00m\]'

  export PS1="${PROJECT_NAME_COLOUR}${PROJECT_NAME}-${ENVIRONMENT_INDICATOR}${RESET_COLOUR}:${PROJECT_DIR_COLOUR}\w${RESET_COLOUR} \$ "

  echo "This is pod '$(hostname)'."
END_BASHRC_LINES

# Mention the pod name again upon logout, in case it might be useful later.
RUN cat <<'END_BASHLOGOUT_LINES' >> /etc/bash/bash_logout
  echo "Good-bye from pod '$(hosthame)'."
END_BASHLOGOUT_LINES