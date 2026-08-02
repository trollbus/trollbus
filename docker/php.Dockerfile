FROM php:8.5.9-cli

RUN apt-get update

# Install ext zip
RUN apt-get install -y zip && \
    apt-get install -y libzip-dev && \
    docker-php-ext-install zip

# Install ext-sockets
RUN docker-php-ext-install sockets

# Install PDO PostgreSQL driver
RUN apt-get install -y libpq-dev && \
    docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && \
    docker-php-ext-install pgsql pdo_pgsql

# Install ext-intl
RUN apt-get install -y libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl

# Install XDebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Install composer
COPY --from=composer:2.8.5 /usr/bin/composer /usr/local/bin/composer
