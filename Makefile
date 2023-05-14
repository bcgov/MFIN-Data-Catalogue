include .env
NAME := $(or $(BASE_IMAGE),$(BASE_IMAGE),bcgov-c/mfin-data-catalogue)
VERSION := $(or $(VERSION),$(VERSION),'latest')
PLATFORM := $(shell uname -s)
$(eval GIT_USERNAME := $(if $(GIT_USERNAME),$(GIT_USERNAME),github-token))
$(eval GIT_PASSWORD := $(if $(GIT_PASSWORD),$(GIT_PASSWORD),$(CI_JOB_TOKEN)))
DOCKER_REPO := https://github.com/drupalwxt/docker-scaffold.git
GET_DOCKER := $(shell [ -d docker ] || git clone --branch 10.1.x $(DOCKER_REPO) docker)
include docker/Makefile
