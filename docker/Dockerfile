FROM php:8.3-apache

# Install system dependencies and PHP extensions required by Moodle
RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libxml2-dev \
    libsodium-dev \
    libexif-dev \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        mysqli \
        pdo_mysql \
        gd \
        intl \
        zip \
        soap \
        exif \
        opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Align www-data UID/GID with host user (1000:1000)
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

# Configure PHP for Moodle
RUN { \
    echo "memory_limit = 512M"; \
    echo "max_input_vars = 5000"; \
    echo "upload_max_filesize = 100M"; \
    echo "post_max_size = 100M"; \
    echo "max_execution_time = 600"; \
    echo "date.timezone = Asia/Seoul"; \
    echo "zend.exception_ignore_args = 1"; \
    echo "opcache.enable = 1"; \
    echo "opcache.memory_consumption = 128"; \
    echo "opcache.max_accelerated_files = 10000"; \
    echo "opcache.revalidate_freq = 60"; \
} > /usr/local/etc/php/conf.d/moodle.ini

# Configure Apache DocumentRoot (/var/www/html/public) and Moodle router
RUN a2enmod rewrite headers && \
    sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf && \
    sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf && \
    { \
        echo "<Directory /var/www/html/public>"; \
        echo "    Options FollowSymLinks"; \
        echo "    AllowOverride All"; \
        echo "    Require all granted"; \
        echo "    AcceptPathInfo On"; \
        echo "    FallbackResource /r.php"; \
        echo "</Directory>"; \
    } > /etc/apache2/conf-available/moodle-router.conf && \
    a2enconf moodle-router

WORKDIR /var/www/html
