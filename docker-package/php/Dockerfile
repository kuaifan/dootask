FROM kuaifan/php:swoole-8.0

ADD ./start.sh /
ADD ./dootask.tar.gz /var/www

RUN apt update && \
    apt install -y libldap2-dev unzip && \
    docker-php-ext-install ldap && \
    if [ -d "/var/www/storage" ]; then cp -r /var/www/storage /var/cache/dootask; fi && \
    chmod +x /start.sh && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/src/php.tar.xz /usr/src/php.tar.xz.asc /usr/src/dootask.tar.gz

ENTRYPOINT "/start.sh"

VOLUME ["/var/www/storage"]

CMD /start.sh
