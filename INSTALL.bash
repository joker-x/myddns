#!/bin/bash

#
# Variables
#

BASEDOMAIN=$BASEDOMAIN
BASEIP=$BASEIP
PASSWORD=$PASSWORD
SCRIPTPATH=$(dirname "$(realpath $0)")
# Colors
NOCOLOR='\033[0m'
RED='\033[0;31m'
GREEN='\033[0;32m'

#
# Functions (tasks)
#

function install_dependencies() {
  echo -e "${GREEN}Install dependencies${NOCOLOR}"
  apt -y update
  apt -y upgrade
  apt install -y coreutils nginx dnsmasq php7.4-fpm
}

function disable_systemd_resolved() {
  echo -e "${GREEN}Disable systemd resolved${NOCOLOR}"
  systemctl disable systemd-resolved
  systemctl stop systemd-resolved
  unlink /etc/resolv.conf
  echo "nameserver 208.67.222.222" > /etc/resolv.conf
}

function configure_nginx() {
  echo -e "${GREEN}Configure Nginx${NOCOLOR}"
  if [ -d /etc/nginx/sites-available ]
  then
    cp "$SCRIPTPATH/config/myddns.nginx" /etc/nginx/sites-available/myddns
    sed -i "s/server_name _;/server_name $BASEDOMAIN;/g" /etc/nginx/sites-available/myddns
    cd /etc/nginx/sites-enabled
    [[ -f myddns ]] && unlink myddns
    ln -s ../sites-available/myddns
  else
    cp "$SCRIPTPATH/config/myddns.nginx" /etc/nginx/conf.d/myddns
    sed -i "s/server_name _;/server_name $BASEDOMAIN;/g" /etc/nginx/conf.d/myddns
  fi
  nginx -t && service nginx restart
}

function configure_dnsmasq() {
  echo -e "${GREEN}Configure dnsmasq${NOCOLOR}"
  cp "$SCRIPTPATH/config/dnsmasq.conf" /etc/dnsmasq.d/myddns
  echo "address=/$BASEDOMAIN/$BASEIP" >> /etc/dnsmasq.d/myddns
  sed -i '/local-service/d' /etc/init.d/dnsmasq
  service dnsmasq restart
}

function copy_www() {
  echo -e "${GREEN}Copy www${NOCOLOR}"
  mkdir -p /var/www/myddns/data
  touch /var/www/myddns/data/hosts
  cp -R "$SCRIPTPATH/public_html" /var/www/myddns/
  chown -R www-data:www-data /var/www/myddns
}

function configure_app() {
  echo -e "${GREEN}Configure application${NOCOLOR}"
  mkdir -p /var/www/myddns/config
  CONFIG=$(cat <<EOF
<?php
  \$basedomain="$BASEDOMAIN";
  \$password="$PASSWORD";
EOF
)
  echo -e "$CONFIG" > /var/www/myddns/config/config.php
}

function install_cron() {
  echo -e "${GREEN}Install cron${NOCOLOR}"
  cp "$SCRIPTPATH/config/myddns-reload" /usr/local/sbin/myddns-reload
  chmod u+x /usr/local/sbin/myddns-reload
  cp "$SCRIPTPATH/config/myddns.cron" /etc/cron.d/myddns
}

function read_args() {
  while [ -z "$BASEDOMAIN" ]
  do
    read -p "(required) BASEDOMAIN = " BASEDOMAIN
  done

  while [ -z "$BASEIP" ]
  do
    read -p "(required) BASEIP = " BASEIP
  done

  [ -z "$PASSWORD" ] && read -p "(optional) PASSWORD = " PASSWORD
}

#
# MAIN
#

[ "$(id -u)" != "0" ] && echo -e "${RED}This script must be run as root${NOCOLOR}" && exit 1

cd "$SCRIPTPATH"
read_args
[ ! -z "$(echo $(service systemd-resolved status) | grep loaded)" ] && \
  disable_systemd_resolved
install_dependencies
copy_www
configure_app
configure_nginx
configure_dnsmasq
install_cron
echo -e "${GREEN}Success${NOCOLOR}"
