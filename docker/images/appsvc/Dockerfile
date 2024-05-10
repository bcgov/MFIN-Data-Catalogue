# https://github.com/drupalwxt/docker-scaffold/blob/10.0.x/Dockerfile
ARG BASE_IMAGE
FROM $BASE_IMAGE as src

ARG VARNISH=false

# Configure supervisor
RUN apk add --no-cache supervisor
RUN mkdir -p /etc/supervisor.d/
RUN mkdir -p /var/log/supervisord/
COPY conf/supervisord.ini /etc/supervisor.d/supervisord.ini

# Configure nginx
RUN apk add --no-cache nginx
RUN mkdir -p /etc/nginx

COPY conf/nginx.conf /etc/nginx/nginx.conf

# Configure cron
COPY tasks/ /etc/periodic/
RUN chmod -R +x /etc/periodic/

# Configure varnish
RUN mkdir -p /etc/varnish
COPY conf/default.vcl /etc/varnish/default.vcl
COPY conf/splash.html /etc/varnish/splash.html

RUN if [ "$VARNISH" = "true" ]; then \
    apk add --no-cache varnish; \
fi

RUN if [ "$REDIS" = "true" ]; then \
    apk --update add redis; \
fi

# ------------------------
# SSH Server support
# Alpine Reference: https://docs.microsoft.com/en-us/azure/app-service/configure-custom-container?pivots=container-linux#enable-ssh
# This is a specfic feature of Azure Web Apps which has its own isolation and should not be used in any other scenario.
# ------------------------

# Install OpenSSH and set the password for root to "Docker!"
RUN apk add openssh \
     && echo "root:Docker!" | chpasswd

# Copy the sshd_config file to the /etc/ssh/ directory
COPY conf/sshd/sshd_config /etc/ssh/

# Copy and configure the ssh_setup file
RUN mkdir -p /tmp
COPY conf/sshd/sshd_setup.sh /tmp
RUN chmod +x /tmp/sshd_setup.sh \
    && (sleep 1;/tmp/sshd_setup.sh 2>&1 > /dev/null)

COPY conf/sshd/sshd_init.sh /etc/ssh/

# Open port 2222 for SSH access
EXPOSE 80 2222
ENTRYPOINT ["/etc/ssh/sshd_init.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor.d/supervisord.ini"]
