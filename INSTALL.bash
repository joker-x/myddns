#!/bin/bash

BASEDOMAIN=$BASEDOMAIN
BASEIP=$BASEIP
PASSWORD=$PASSWORD
SCRIPTPATH=$(dirname "$(realpath $0)")

function install_dependencies() {
  apt update
  apt upgrade
  apt install coreutils nginx dnsmasq php7.4-fpm
}

function disable_systemd_resolved() {
  systemctl disable systemd-resolved
  systemctl stop systemd-resolved
  unlink /etc/resolv.conf
  echo "nameserver 8.8.8.8" > /etc/resolv.conf
}

function configure_nginx() {
  cp "$SCRIPTPATH/config/myddns.nginx" /etc/nginx/sites-available/myddns
  cd /etc/nginx/sites-enabled
  [[ -f default ]] && unlink default
  [[ -f myddns ]] && unlink myddns
  ln -s ../sites-available/myddns
  nginx -t && service nginx restart
}

function configure_dnsmasq() {
  cp "$SCRIPTPATH/config/dnsmasq.conf" /etc/dnsmasq.d/myddns
  echo "address=/$BASEDOMAIN/$BASEIP" >> /etc/dnsmasq.d/myddns
  sed -i '/local-service/d' /etc/init.d/dnsmasq
  service dnsmasq restart
}

function copy_www() {
  mkdir -p /var/www/myddns/data
  touch /var/www/myddns/data/hosts
  cp -R "$SCRIPTPATH"/* /var/www/myddns/
  chown -R www-data:www-data /var/www/myddns
}

function configure_app() {
  CONFIG=$(cat <<EOF
<?php
  \$basedomain="$BASEDOMAIN";
  \$password="$PASSWORD";
EOF
)
  echo -e "$CONFIG" > /var/www/myddns/config/config.php
}

function install_cron() {
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

[ "$(id -u)" != "0" ] && echo "This script must be run as root" && exit 1

cd "$SCRIPTPATH"
read_args
disable_systemd_resolved
install_dependencies
copy_www
configure_app
configure_nginx
configure_dnsmasq
install_cron
