FROM composer:2 AS composer
FROM php:cli

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
