#!/bin/bash

apt update
apt upgrade

systemctl disable systemd-resolved
systemctl stop systemd-resolved
unlink /etc/resolv.conf
echo "nameserver 8.8.8.8" > /etc/resolv.conf

apt install nginx dnsmasq php7.4-fpm

cp config/dnsmasq.conf /etc/dnsmasq.d/myddns
cp config/myddns.nginx /etc/nginx/sites-available/myddns
cd /etc/nginx/sites-enabled
unlink default
unlink myddns
ln -s ../sites-available/myddns
nginx -t && service nginx restart
cd /root/myddns
sed -i '/local-service/d' /etc/init.d/dnsmasq
mkdir -p /var/www/myddns/data
touch /var/www/myddns/data/hosts
cp -R * /var/www/myddns/
chown -R www-data:www-data /var/www/myddns
service dnsmasq restart
cp config/myddns-reload /usr/local/sbin/myddns-reload
cp config/myddns.cron /etc/cron.d/myddns
