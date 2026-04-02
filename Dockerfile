FROM php:8.4-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        default-mysql-client \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

RUN apt-get update && apt-get install -y nodejs npm