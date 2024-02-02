FROM php:8.2-apache

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
RUN apt-get update && apt-get upgrade -y

COPY . /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]