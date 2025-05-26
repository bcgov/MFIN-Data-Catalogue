include .env

NAME := $(or $(BASE_IMAGE),$(BASE_IMAGE),drupalwxt/site-wxt)
VERSION := $(or $(VERSION),$(VERSION),'latest')
PLATFORM := $(shell uname -s)

DB_NAME := $(or $(DB_NAME),$(DB_NAME),'wxt')
DB_TYPE := $(or $(DB_TYPE),$(DB_TYPE),'mysql')
DB_PORT := $(or $(DB_PORT),$(DB_PORT),'3306')
PROFILE_NAME := $(or $(PROFILE_NAME),$(PROFILE_NAME),'wxt')

all: base

base:
	mkdir -p config/sync html/modules/custom html/themes/custom
	docker build -f docker/Dockerfile \
	    -t $(NAME):$(VERSION) \
	    --build-arg SSH_PRIVATE_KEY="$$(test -f $$HOME/.ssh/id_rsa && base64 $$HOME/.ssh/id_rsa)" \
	    --no-cache \
	    --build-arg http_proxy=$$HTTP_PROXY \
	    --build-arg HTTP_PROXY=$$HTTP_PROXY \
	    --build-arg https_proxy=$$HTTP_PROXY \
	    --build-arg HTTPS_PROXY=$$HTTP_PROXY \
	    --build-arg no_proxy=$$NO_PROXY \
	    --build-arg NO_PROXY=$$NO_PROXY \
	    --build-arg GIT_USERNAME=$(GIT_USERNAME) \
	    --build-arg GIT_PASSWORD=$(GIT_PASSWORD) .

behat:
	./docker/bin/behat -vv -c behat.yml --colors

build: all

clean: clean_composer

clean_composer:
	rm -rf html
	rm -rf vendor
	rm -f composer.lock
	composer clear-cache

clean_docker:
	rm -rf docker
	git clone $(DOCKER_REPO) docker
	[ "$(shell docker images -q --filter "dangling=true")" = "" ] || docker rmi -f $(shell docker images -q --filter "dangling=true")
	[ "$(shell docker ps -a -q -f name=${DOCKER_NAME}_)" = "" ] || docker rm -f $(shell docker ps -a -q -f name=${DOCKER_NAME}_)
	[ "$(shell docker images -q -f reference=${DOCKER_IMAGE}_*)" = "" ] || docker rmi -f $(shell docker images -q -f reference=*${DOCKER_IMAGE}_*)
	[ "$(shell docker images -q -f reference=${NAME})" = "" ] || docker rmi -f $(shell docker images -q -f reference=${NAME})

clean_drupal: clean_composer composer_install docker_stop docker_start drupal_install

clean_site: clean_composer composer_install clean_docker base docker_build drupal_install
	./docker/bin/drush cr

composer_install:
	composer install

docker_build:
	docker compose build --no-cache
	docker compose up -d

docker_start:
	docker compose up -d

docker_stop:
	docker compose down

drupal_cs:
	mkdir -p html/core/
	cp docker/conf/drupal/phpcs.xml html/core/phpcs.xml
	cp docker/conf/drupal/phpunit.xml html/core/phpunit.xml

drupal_install:
	if [ "$(CI)" ]; then \
		docker compose exec -T cli bash /var/www/docker/bin/cli drupal-first-run $(DB_NAME) $(DB_TYPE) $(DB_PORT) $(PROFILE_NAME); \
	else \
		docker compose exec cli bash /var/www/docker/bin/cli drupal-first-run $(DB_NAME) $(DB_TYPE) $(DB_PORT) $(PROFILE_NAME); \
	fi

drupal_init:
	if [ "$(CI)" ]; then \
		docker compose exec -T cli bash /var/www/docker/bin/cli drupal-init $(DB_NAME) $(DB_TYPE) $(DB_PORT) $(PROFILE_NAME); \
	else \
		docker compose exec cli bash /var/www/docker/bin/cli drupal-init $(DB_NAME) $(DB_TYPE) $(DB_PORT) $(PROFILE_NAME); \
	fi

drupal_migrate:
	if [ "$(CI)" ]; then \
		docker compose exec -T cli bash /var/www/docker/bin/cli drupal-migrate $(DB_NAME) $(DB_TYPE) $(DB_PORT) $(PROFILE_NAME); \
	else \
		docker compose exec cli bash /var/www/docker/bin/cli drupal-migrate $(DB_NAME) $(DB_TYPE) $(DB_PORT) $(PROFILE_NAME); \
	fi

drupal_perm:
	if [ "$(CI)" ]; then \
		docker compose exec -T cli bash /var/www/docker/bin/cli drupal-perm $(DB_NAME) $(DB_TYPE) $(DB_PORT) $(PROFILE_NAME); \
	else \
		docker compose exec cli bash /var/www/docker/bin/cli drupal-perm $(DB_NAME) $(DB_TYPE) $(DB_PORT) $(PROFILE_NAME); \
	fi

drush_archive:
	./docker/bin/drush archive-dump --destination="/var/www/files_private/drupal$$(date +%Y%m%d_%H%M%S).tgz" \
                                  --generator="Drupal"

lint:
	./docker/bin/lint

# http://stackoverflow.com/questions/4219255/how-do-you-get-the-list-of-targets-in-a-makefile
list:
	@$(MAKE) -pRrq -f $(lastword $(MAKEFILE_LIST)) : 2>/dev/null | awk -v RS= -F: '/^# File/,/^# Finished Make data base/ {if ($$1 !~ "^[#.]") {print $$1}}' | sort | egrep -v -e '^[^[:alnum:]]' -e '^$@$$' | xargs

phpcs: drupal_cs
	./docker/bin/phpcs --config-set installed_paths /var/www/vendor/drupal/coder/coder_sniffer

	./docker/bin/phpcs --standard=/var/www/html/core/phpcs.xml \
	    --extensions=php,module,inc,install,test,profile,theme \
	    --report=full \
	    --colors \
	    --ignore=/var/www/html/profiles/$(PROFILE_NAME)/modules/custom/wxt_test \
	    --ignore=*.css \
	    --ignore=*.txt \
	    --ignore=*.md \
	    --ignore=/var/www/html/*/custom/*/*.info.yml \
	    /var/www/html/modules/contrib/wxt_library \
	    /var/www/html/themes/contrib/wxt_bootstrap \
	    /var/www/html/profiles/$(PROFILE_NAME)/modules/custom

	./docker/bin/phpcs --standard=/var/www/html/core/phpcs.xml \
	    --extensions=php,module,inc,install,test,profile,theme \
	    --report=full \
	    --colors \
	    --ignore=*.md \
	    -l \
	    /var/www/html/profiles/$(PROFILE_NAME)

phpunit:
	./docker/bin/phpunit --colors=always \
	    -c /var/www/html/core/phpunit.xml \
	    --testsuite=kernel \
	    --group=$(PROFILE_NAME)

	./docker/bin/phpunit --colors=always \
	    -c /var/www/html/core/phpunit.xml \
	    --testsuite=unit \
	    --group=$(PROFILE_NAME)

release: tag_latest
	@if ! docker images $(NAME) | awk '{ print $$2 }' | grep -q -F $(VERSION); then echo "$(NAME) version $(VERSION) is not yet built. Please run 'make base'"; false; fi
	docker push $(NAME)
	@echo "*** Don't forget to create a tag. git tag rel-$(VERSION) && git push origin rel-$(VERSION)"

tag_latest:
	docker tag -f $(NAME):$(VERSION) $(NAME):latest

test: phpcs phpunit behat

update: base
	git pull origin 8.x
	composer update
	docker compose build --no-cache
	docker compose up -d

.PHONY: \
	all \
	base \
	behat \
	build \
	clean \
	clean_composer \
	clean_docker \
	clean_site \
	composer_install \
	docker_build \
	drupal_cs \
	drupal_install \
	drupal_migrate \
	drush_archive \
	lint \
	list \
	phpcs \
	phpunit \
	release \
	tag_latest \
	test \
	update
