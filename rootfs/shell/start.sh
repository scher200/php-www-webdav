#!/bin/bash

# Set the root directory to server
if [ ! -z "$SHARED_DIR" ]; then
    echo "$SHARED_DIR choosen to serve"
else
    SHARED_DIR="/var/www"
    echo "Standard SHARED_DIR /var/www choosen to serve"
fi
mkdir -p $SHARED_DIR
rm /sabre/files
ln -sf $SHARED_DIR /sabre/files

## CHOWN GROUP $SHARED_DIR FOLDER
chown -R :xfs $SHARED_DIR
chmod -R g+w $SHARED_DIR
echo "-> Owned ${SHARED_DIR} folder with linux group 33 (debian www-data) and set group write rights"
## END CHOWN GROUP HTML FOLDER


# Disable opcache?
if [[ -v NO_OPCACHE ]]; then
    sed -i -e "s/zend_extension=opcache.so/;zend_extension=opcache.so/g" /etc/php7/conf.d/zend-opcache.ini
    echo "OPCACHE is turned OFF"
fi

# Tweak nginx to match the workers to cpu's
procs=$(cat /proc/cpuinfo | grep processor | wc -l)
sed -i -e "s/worker_processes 5/worker_processes $procs/" /etc/nginx/nginx.conf
echo "nginx workers tweaked to ${procs} cpu's"

# Set the password
if [ ! -z "$AUTH_USER" ]; then
  echo "${AUTH_USER}, ${AUTH_PASS} are set as login and password"
  sed -i "s%array('admin' => 'admin');%array('${AUTH_USER}' => '${AUTH_PASS}');%g" /sabre/server.php
else
  echo -e "WARNING: No AUTH_USER and AUTH_PASS are set\radmin, admin are set as login and password"
fi

# remove global traces
export AUTH_USER=""
export AUTH_PASS=""

# Start supervisord and services
/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
