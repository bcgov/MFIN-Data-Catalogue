include .env
NAME := $(or $(BASE_IMAGE),$(BASE_IMAGE),bcgov-c/newbbgitreponame)
VERSION := $(or $(VERSION),$(VERSION),'latest')
PLATFORM := $(shell uname -s)
$(eval GIT_USERNAME := $(if $(GIT_USERNAME),$(GIT_USERNAME),github-token))
$(eval GIT_PASSWORD := $(if $(GIT_PASSWORD),$(GIT_PASSWORD),$(CI_JOB_TOKEN)))
# TODO: Remove this GET_DOCKER -- we've cloned it into /docker already. Search for refs to these two env-vars.
DOCKER_REPO := https://github.com/drupalwxt/docker-scaffold.git
GET_DOCKER := $(shell [ -d docker ] || git clone --branch 10.1.x $(DOCKER_REPO) docker)
include docker/Makefile
