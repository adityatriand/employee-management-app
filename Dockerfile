# Use a more reliable base image with retry logic
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and services
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    default-mysql-server \
    default-mysql-client \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer (with retry logic)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer || \
    (sleep 5 && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer)

# Install Node.js and npm
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs

# Copy application files
COPY . /var/www/html

# Copy configuration files
COPY docker/nginx/default.conf /etc/nginx/sites-available/default
RUN rm -f /etc/nginx/sites-enabled/default && \
    ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

# Create MySQL directories and set permissions
RUN mkdir -p /var/lib/mysql /var/run/mysqld /var/log/mysql \
    && chown -R mysql:mysql /var/lib/mysql /var/run/mysqld /var/log/mysql \
    && chmod 777 /var/run/mysqld

# Set Laravel permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Create startup script
RUN echo '#!/bin/bash\n\
cd /var/www/html\n\
\n\
# Install Composer dependencies if vendor directory does not exist\n\
if [ ! -d "vendor" ]; then\n\
    echo "Installing Composer dependencies..."\n\
    composer install --no-interaction --prefer-dist 2>&1 || {\n\
        echo "Composer install failed, trying update..."\n\
        composer update --no-interaction --prefer-dist 2>&1 || true\n\
    }\n\
fi\n\
\n\
# Install Node dependencies if node_modules does not exist\n\
if [ ! -d "node_modules" ]; then\n\
    echo "Installing Node dependencies..."\n\
    npm install 2>&1 || echo "npm install failed, continuing..."\n\
fi\n\
\n\
# Build assets if not already built\n\
if [ ! -f "public/mix-manifest.json" ] && [ ! -f "public/build/manifest.json" ]; then\n\
    echo "Building frontend assets..."\n\
    npm run production 2>&1 || npm run dev 2>&1 || echo "Asset build failed, continuing..."\n\
fi\n\
\n\
# Start MySQL\n\
echo "Starting MySQL..."\n\
service mysql start 2>&1 || mysqld_safe --user=mysql &\n\
sleep 5\n\
\n\
# Wait for MySQL to be ready\n\
for i in {1..30}; do\n\
    mysql -uroot -e "SELECT 1" >/dev/null 2>&1 && break\n\
    echo "Waiting for MySQL... ($i/30)"\n\
    sleep 1\n\
done\n\
\n\
# Setup database if not exists\n\
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS employee_management;" 2>/dev/null || true\n\
mysql -uroot -e "CREATE USER IF NOT EXISTS '\''laravel_user'\''@'\''localhost'\'' IDENTIFIED BY '\''laravel_password'\'';" 2>/dev/null || true\n\
mysql -uroot -e "CREATE USER IF NOT EXISTS '\''laravel_user'\''@'\''%'\'' IDENTIFIED BY '\''laravel_password'\'';" 2>/dev/null || true\n\
mysql -uroot -e "GRANT ALL PRIVILEGES ON employee_management.* TO '\''laravel_user'\''@'\''localhost'\'';" 2>/dev/null || true\n\
mysql -uroot -e "GRANT ALL PRIVILEGES ON employee_management.* TO '\''laravel_user'\''@'\''%'\'';" 2>/dev/null || true\n\
mysql -uroot -e "FLUSH PRIVILEGES;" 2>/dev/null || true\n\
\n\
# Start supervisor (this must succeed)\n\
echo "Starting services with supervisor..."\n\
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf' > /start.sh \
    && chmod +x /start.sh

# Expose ports
EXPOSE 80 3306

# Start all services
CMD ["/start.sh"]
