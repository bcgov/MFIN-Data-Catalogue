# CHANGELOG

## 10.2.x

- TBD

## 9.5.x

- Instead of running `docker-compose` you must now call `docker compose`
- The default password for a Drupal install is now set to the current year
- You will need to update your symlinks to the new naming of the docker-compose files

```sh
ln -s docker/docker-compose.base.yml docker-compose.base.yml
ln -s docker/docker-compose.ci.yml docker-compose.ci.yml
ln -s docker/docker-compose.yml docker-compose.yml
```

- Please take not that docker images now using hyphens instead of underscores

> "${DOCKER_IMAGE}_cli" in the `bin` folder now is called "${DOCKER_IMAGE}-cli"

- The `settings.php` is no longer hardcoded and uses getenv for most parameters
- The $DB_TYPE environment variable now controls which database is used
- The $DB_PORT environment variable now controls which database port is used

> You can see this in use by the [WxT CI Builds](https://github.com/drupalwxt/wxt/blob/4.5.x/.github/workflows/build.yml#L36)
