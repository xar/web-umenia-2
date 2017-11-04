FROM ambientum/php-alpine:7.1

WORKDIR /var/www/app

RUN npm install

RUN composer install

CMD ["/var/www/app/Docker/_boot.sh"]
