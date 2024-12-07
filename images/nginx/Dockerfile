ARG BASE_IMAGE
FROM $BASE_IMAGE as src

FROM nginx:alpine

RUN chown -R www-data:www-data /var/cache/nginx /var/run /var/log/nginx && \
    chmod -R 755 /var/cache/nginx /var/run /var/log/nginx

COPY --from=src /var/www/html /var/www/html
