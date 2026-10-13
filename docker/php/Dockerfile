FROM php:8.3-fpm-alpine

# Install system dependencies, LDAP development headers, and tools
RUN apk add --no-cache \
    bash \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    openldap-dev \
    oniguruma-dev \
    mariadb-connector-c-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    icu-dev \
    && mkdir -p /etc/openldap /etc/ldap \
    && echo "TLS_REQCERT never" >> /etc/openldap/ldap.conf \
    && echo "TLS_REQCERT never" >> /etc/ldap/ldap.conf

# Configure and compile PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure ldap --with-libdir=lib/ \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        opcache \
        ldap

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Run composer install for production optimization (if vendor not pre-packaged)
RUN if [ ! -d "vendor" ]; then composer install --no-dev --optimize-autoloader --no-interaction; fi

# Fix permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
