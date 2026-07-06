FROM dunglas/frankenphp:php8.5

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN install-php-extensions pdo_mysql intl zip
