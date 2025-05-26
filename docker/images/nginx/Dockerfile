ARG BASE_IMAGE
FROM $BASE_IMAGE as src

FROM nginx:alpine

RUN chgrp -R root /var/cache/nginx /var/run /var/log/nginx && \
    chmod -R 770 /var/cache/nginx /var/run /var/log/nginx

COPY --from=src /var/www/html /var/www/html
