FROM debian:bullseye

ARG BASEDOMAIN
ARG BASEIP 
ARG PASSWORD

ARG DEBIAN_FRONTEND=noninteractive

# dependencies
RUN apt update
RUN apt purge -y exim4*
RUN apt install -y coreutils cron nginx dnsmasq-base php7.4-fpm supervisor nano vim net-tools
RUN apt clean -y
RUN apt autoremove -y

# php
RUN mkdir -p /run/php && chown www-data:www-data /run/php && chmod 7755 /run/php 

# nginx
COPY config/myddns.nginx /etc/nginx/sites-available/myddns
RUN sed -i "s/server_name _;/server_name $BASEDOMAIN;/g" /etc/nginx/sites-available/myddns && \
  cd /etc/nginx/sites-enabled && ln -s ../sites-available/myddns
#RUN echo "\ndaemon off;\n" >> /etc/nginx/nginx.conf

# dnsmasq
COPY config/dnsmasq.conf /etc/dnsmasq.d/myddns
RUN echo "address=/$BASEDOMAIN/$BASEIP" >> /etc/dnsmasq.d/myddns

# copy-www
RUN mkdir -p /var/www/myddns/data
#RUN touch /var/www/myddns/data/hosts
COPY ./public_html /var/www/myddns/public_html
RUN chown -R www-data:www-data /var/www/myddns

# configure-app
RUN mkdir -p /var/www/myddns/config
RUN echo "<?php \n\$basedomain=\"$BASEDOMAIN\";\n\$password=\"$PASSWORD\";\n" > /var/www/myddns/config/config.php

# supervisord
COPY config/supervisord.conf /etc/supervisor/supervisord.conf

# cron
COPY config/myddns-reload /usr/local/sbin/myddns-reload
RUN chmod u+x /usr/local/sbin/myddns-reload
COPY config/myddns.cron /etc/cron.d/myddns

COPY docker-entrypoint /root/docker-entrypoint
CMD ["/root/docker-entrypoint"]

EXPOSE 80
EXPOSE 53
EXPOSE 53/udp
