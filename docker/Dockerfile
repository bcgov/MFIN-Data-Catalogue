# https://github.com/docker-library/drupal/blob/master/10.2/php8.2/fpm-alpine3.19/Dockerfile
FROM drupal:10.6.1-php8.3-fpm-alpine3.23

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
ARG DRUPAL_REPO_AUTHOR_INFO
ARG DRUPAL_REPO_COMMITTER
ARG DRUPAL_REPO_COMMIT_DATETIME
ARG DRUPAL_REPO_TAG_TO_DEPLOY
ARG DRUPAL_REPO_COMMIT_MESSAGE

ENV DRUPAL_REPO_SHA=$GITHUB_SHA
ENV DRUPAL_REPO_AUTHOR_INFO="$DRUPAL_REPO_AUTHOR_INFO"
ENV DRUPAL_REPO_COMMITTER="$DRUPAL_REPO_COMMITTER"
ENV DRUPAL_REPO_COMMIT_DATETIME="$DRUPAL_REPO_COMMIT_DATETIME"
ENV DRUPAL_REPO_TAG_TO_DEPLOY=$DRUPAL_REPO_TAG_TO_DEPLOY
ENV DRUPAL_REPO_COMMIT_MESSAGE="$DRUPAL_REPO_COMMIT_MESSAGE"

RUN apk --no-cache add fcgi \
    && curl -sSL -o /usr/local/bin/php-fpm-healthcheck \
       https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/a2d45de918787f761754b96b94a59f4f6acebc25/php-fpm-healthcheck \
    && echo "53bc616c4a30f029b98bff48fdeb0c4da252cb11e4f86656a8222a67dc4e5009  /usr/local/bin/php-fpm-healthcheck" | sha256sum -c - \
    && chmod +x /usr/local/bin/php-fpm-healthcheck
COPY docker/conf/php-fpm/status.conf /usr/local/etc/php-fpm.d/

HEALTHCHECK --interval=5s --timeout=10s --start-period=5s --retries=3 CMD [ "php-fpm-healthcheck" ]

# Install additional extensions
RUN apk --update add --no-cache bash \
                                git \
                                gzip \
                                mysql-client \
                                mariadb-connector-c \
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

# TLS Enforcement by MariaDB Client in Linux Alpine Base Image
# https://github.com/laravel/framework/discussions/54267
COPY docker/conf/my.cnf /etc/my.cnf.d/my.cnf
COPY docker/conf/drush.yml ~/.drush/drush.yml

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
ENV PHPREDIS_VERSION 6.1.0
RUN mkdir -p /usr/src/php/ext/redis \
    && curl -L https://github.com/phpredis/phpredis/archive/$PHPREDIS_VERSION.tar.gz | tar xvz -C /usr/src/php/ext/redis --strip 1 \
    && echo 'redis' >> /usr/src/php-available-exts \
    && docker-php-ext-install redis

# Composer recommended settings
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_VERSION 2.5.8
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
END_BASHRC_LINES

# Set the bash prompt colours and text.
# "\[\033[38;2;0;0;0m\]"   means set FG to RGB black
# "\[\033[48;2;0;255;0m\]" means set BG to RGB green
# \w is current directory
# \$ is prompt ($, or might be # for root)
RUN cat <<'END_BASHRC_LINES' >> /etc/bash/bashrc
  case "$ENVIRONMENT_NAME" in
    "dev")   ENVIRONMENT_COLOUR='\[\033[38;2;0;0;0m\]\[\033[48;2;160;255;160m\]'    ;; # Black on green.
    "test")  ENVIRONMENT_COLOUR='\[\033[38;2;0;0;0m\]\[\033[48;2;230;230;96m\]'    ;; # Black on yellow.
    "prod")  ENVIRONMENT_COLOUR='\[\033[38;2;255;255;255m\]\[\033[48;2;235;0;0m\]' ;; # White on red.
    *) echo "Bad environment name, '$ENV_NAME'." ;;
  esac

  PROJECT_NAME='fdc'
  PROJECT_COLOUR="\[\033[38;2;170;128;255m\]"  # Hue:260, Sat:100%, Lum:75% (Iris)
  RESET_COLOUR='\[\033[00m\]'
  
  export PS1="${PROJECT_NAME}-${ENVIRONMENT_COLOUR}${ENVIRONMENT_NAME}${RESET_COLOUR}(${DRUPAL_REPO_TAG_TO_DEPLOY}):${PROJECT_COLOUR}\w${RESET_COLOUR} \$ "

  # Remove any blank lines from the commit message, and whitespace from end of each line:
  TERSE_DRUPAL_REPO_COMMIT_MESSAGE=$(printf "%s" "$LKJ" |  grep -v '^[[:space:]]*$' | sed 's/[[:space:]]\+$//')

  # To make it look like this:
  #  
  # ----------- Connected to 'mfin-data-catalogue-drupal-76db898bd4-drwzl'. -----------
  # -- https://github.com/bcgov-c/tenant-gitops-ea352d/commit/4468730
  # -- https://github.com/bcgov/MFIN-Data-Catalogue/commit/ceaa1ec (Fri 2026-03-13 16:42, Dan Howard) 
  # --     #XYZ-1234: Merge 'View' and 'Edit' into single view field, for visual consistency with other views...
  # --     - did thing A
  # --     - and thing B
  # fdc-dev(1.0.44g):/var/www/html $ 

  echo
  echo "----------- Connected to '$(hostname)'. -----------"
  echo "-- https://github.com/bcgov-c/tenant-gitops-ea352d/commit/${DEPLOYED_GITOPS_REVISION_SHA:0:7}"
  echo "-- https://github.com/bcgov/MFIN-Data-Catalogue/commit/${DRUPAL_REPO_SHA:0:7}, $DRUPAL_REPO_COMMIT_DATETIME, $DRUPAL_REPO_COMMITTER"
  echo "$DRUPAL_REPO_COMMIT_MESSAGE" | awk '{print "--    "$0}'
  echo "--"

END_BASHRC_LINES


# Mention the pod name again upon logout, in case it might be useful later.
RUN cat <<'END_BASHRC_LINES' >> /etc/bash/bashrc

  # Guard to only affect interactive shells
  if [[ $- == *i* ]]; then
    _exit_message() {
      echo "-------- Disconnecting from '$(hostname)'. --------"
      echo
    }

    # Chain with any existing EXIT trap (avoid overwriting)
    existing_trap=$(trap -p EXIT | sed -E "s/^trap -- '(.*)' EXIT$/\1/")
    if [[ -n "$existing_trap" ]]; then
      trap '{ '"$existing_trap"'; } ; _exit_message' EXIT
    else
      trap '_exit_message' EXIT
    fi
  fi

END_BASHRC_LINES
