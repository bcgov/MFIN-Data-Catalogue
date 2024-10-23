#!/bin/sh

echo "Starting SSH..."
/usr/sbin/sshd

# Get environment variables to show up in SSH session
# https://docs.microsoft.com/en-us/answers/questions/179503/webapp-for-containers-app-settings-not-passed-thro.html
eval $(printenv | sed -n "s/^\([^=]\+\)=\(.*\)$/export \1=\2/p" | sed 's/"/\\\"/g' | sed '/=/s//="/' | sed 's/$/"/' >> /etc/profile)

echo "Starting PHP-FPM..."
exec docker-php-entrypoint "$@"
