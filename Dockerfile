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
    redis-server \
    wget \
    unzip \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install MinIO binary
RUN wget -q https://dl.min.io/server/minio/release/linux-amd64/minio -O /usr/local/bin/minio \
    && chmod +x /usr/local/bin/minio \
    || echo "MinIO download failed, will use external MinIO service"

# Install Prometheus binary
RUN wget -q https://github.com/prometheus/prometheus/releases/download/v2.48.0/prometheus-2.48.0.linux-amd64.tar.gz -O /tmp/prometheus.tar.gz \
    && tar -xzf /tmp/prometheus.tar.gz -C /tmp \
    && mv /tmp/prometheus-2.48.0.linux-amd64/prometheus /usr/local/bin/prometheus \
    && mv /tmp/prometheus-2.48.0.linux-amd64/promtool /usr/local/bin/promtool \
    && chmod +x /usr/local/bin/prometheus /usr/local/bin/promtool \
    && rm -rf /tmp/prometheus* \
    || echo "Prometheus download failed"

# Install Grafana binary
RUN GRAFANA_VERSION="10.2.2" && \
    GRAFANA_URL="https://dl.grafana.com/oss/release/grafana-${GRAFANA_VERSION}.linux-amd64.tar.gz" && \
    (wget -q --timeout=30 --tries=3 "$GRAFANA_URL" -O /tmp/grafana.tar.gz || \
     curl -fL --connect-timeout 30 --max-time 300 "$GRAFANA_URL" -o /tmp/grafana.tar.gz) && \
    tar -xzf /tmp/grafana.tar.gz -C /tmp && \
    GRAFANA_DIR=$(find /tmp -maxdepth 1 -type d -name "grafana-*" | head -1) && \
    cp "$GRAFANA_DIR/bin/grafana" /usr/local/bin/ 2>/dev/null || true && \
    cp "$GRAFANA_DIR/bin/grafana-server" /usr/local/bin/ && \
    cp "$GRAFANA_DIR/bin/grafana-cli" /usr/local/bin/ && \
    chmod +x /usr/local/bin/grafana* && \
    mkdir -p /etc/grafana /var/lib/grafana /usr/share/grafana && \
    cp -r "$GRAFANA_DIR/public" /usr/share/grafana/ 2>/dev/null || true && \
    cp -r "$GRAFANA_DIR/conf" /usr/share/grafana/ 2>/dev/null || true && \
    rm -rf /tmp/grafana*


# Install Loki binary
RUN wget -q https://github.com/grafana/loki/releases/download/v2.9.2/loki-linux-amd64.zip -O /tmp/loki.zip \
    && unzip -q /tmp/loki.zip -d /tmp \
    && mv /tmp/loki-linux-amd64 /usr/local/bin/loki \
    && chmod +x /usr/local/bin/loki \
    && rm -f /tmp/loki.zip \
    || echo "Loki download failed"

# Install Promtail binary (detect architecture)
RUN ARCH=$(uname -m) && \
    if [ "$ARCH" = "aarch64" ] || [ "$ARCH" = "arm64" ]; then \
        PROMTAIL_ARCH="arm64"; \
    else \
        PROMTAIL_ARCH="amd64"; \
    fi && \
    wget -q "https://github.com/grafana/loki/releases/download/v2.9.2/promtail-linux-${PROMTAIL_ARCH}.zip" -O /tmp/promtail.zip \
    && unzip -q /tmp/promtail.zip -d /tmp \
    && mv /tmp/promtail-linux-${PROMTAIL_ARCH} /usr/local/bin/promtail \
    && chmod +x /usr/local/bin/promtail \
    && rm -f /tmp/promtail.zip \
    || echo "Promtail download failed"

# Create Redis user and directories
RUN useradd -r -s /bin/false redis || true \
    && mkdir -p /var/lib/redis /var/log/redis \
    && chown -R redis:redis /var/lib/redis /var/log/redis || true

# Create MinIO directories
RUN mkdir -p /var/lib/minio /var/log/minio \
    && chown -R root:root /var/lib/minio /var/log/minio

# Create monitoring service directories
RUN mkdir -p /var/lib/prometheus /var/log/prometheus \
    && mkdir -p /var/lib/grafana /var/log/grafana /etc/grafana /usr/share/grafana \
    && mkdir -p /var/lib/loki /var/log/loki \
    && mkdir -p /var/log/promtail \
    && chown -R root:root /var/lib/prometheus /var/log/prometheus \
    && chown -R root:root /var/lib/grafana /var/log/grafana /etc/grafana /usr/share/grafana \
    && chown -R root:root /var/lib/loki /var/log/loki \
    && chown -R root:root /var/log/promtail

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Redis extension (phpredis) - optional but recommended for better performance
# If this fails, Predis (pure PHP) will be used instead
RUN apt-get update && apt-get install -y --no-install-recommends \
    $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y $PHPIZE_DEPS \
    && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/* || \
    echo "Redis extension installation failed, will use Predis instead"

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
COPY docker/grafana/grafana.ini /etc/grafana/grafana.ini
COPY docker/grafana/provisioning /etc/grafana/provisioning
COPY docker/grafana/dashboards /var/lib/grafana/dashboards

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
# Initialize Redis data directory\n\
mkdir -p /var/lib/redis\n\
chown -R redis:redis /var/lib/redis 2>/dev/null || chown -R 999:999 /var/lib/redis 2>/dev/null || true\n\
\n\
# Initialize MinIO data directory\n\
mkdir -p /var/lib/minio\n\
chown -R root:root /var/lib/minio 2>/dev/null || true\n\
chmod 755 /var/lib/minio 2>/dev/null || true\n\
\n\
# Initialize monitoring directories\n\
mkdir -p /var/lib/prometheus /var/lib/grafana /var/lib/loki\n\
chown -R root:root /var/lib/prometheus /var/lib/grafana /var/lib/loki 2>/dev/null || true\n\
chmod 755 /var/lib/prometheus /var/lib/grafana /var/lib/loki 2>/dev/null || true\n\
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
# Supervisor will start: PHP-FPM, Nginx, MySQL, Redis, MinIO, Queue Worker\n\
echo "Starting services with supervisor..."\n\
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf' > /start.sh \
    && chmod +x /start.sh

# Expose ports
EXPOSE 80 3306

# Start all services
CMD ["/start.sh"]
