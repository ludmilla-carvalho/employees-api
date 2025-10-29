# Dockerfile
FROM php:8.4-fpm

# Arguments for mysql and other
ARG UID=1000
ARG GID=1000

# env
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/composer

# Install system deps
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libcurl4-openssl-dev \
    curl \
    gnupg2 \
    ca-certificates \
    libmcrypt-dev \
    procps \
    supervisor \
    pkg-config \
    build-essential \
    locales \
    rsync \
    libicu-dev \
    nano \
    libjpeg-dev \
    libfreetype6-dev
    
# Install php extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip sockets intl && docker-php-ext-configure gd --with-freetype --with-jpeg && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PCOV for test coverage
RUN pecl install pcov \
    && docker-php-ext-enable pcov \
    && echo "pcov.enabled=1" >> /usr/local/etc/php/conf.d/pcov.ini \
    && echo "pcov.directory=/var/www/html" >> /usr/local/etc/php/conf.d/pcov.ini

# Install Redis extension via PECL
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer (latest)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install NodeJS & npm (Node 20.x as example; tweak if needed)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest

# Create user and group to match host
RUN groupadd -g ${GID} convenia && useradd -u ${UID} -g ${GID} -m -s /bin/bash convenia

# Set working dir
WORKDIR /var/www/html

# Copy composer files early for layer caching
#COPY composer.json ./

# Copy project files
COPY . .

# Install PHP dependencies (will be run as root; composer will set perms later)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader



# Set ownership
RUN chown -R convenia:convenia /var/www/html && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose socket or port (we'll use unix socket with nginx)
EXPOSE 9000

# Entrypoint to run supervisor or artisan commands
COPY .docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Default user (you can override in docker compose)
USER convenia

# Default command
CMD ["php-fpm"]
