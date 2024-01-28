FROM php

RUN docker-php-ext-install mysqli
RUN docker-php-ext-enable mysqli

COPY ./src/ .

EXPOSE 80