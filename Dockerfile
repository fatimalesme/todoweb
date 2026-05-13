FROM php:8.2-apache

# esta línea es la que instala y activa la extensión para bases de datos
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

COPY . /var/www/html/
EXPOSE 80