#!/bin/bash

# Docker Setup Script for Employee Management App
# This script helps set up the Laravel application with Docker (Single Image)

echo "🚀 Setting up Employee Management App with Docker (Single Image)..."

# Check if .env exists
if [ ! -f .env ]; then
    echo "📝 Creating .env file from .env.example..."
    if [ -f .env.example ]; then
        cp .env.example .env
    else
        echo "⚠️  .env.example not found. Creating basic .env file..."
        cat > .env << EOF
APP_NAME=EmployeeManagement
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_LEVEL=debug

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=employee_management
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_password

# Redis Configuration (set automatically in Docker via docker-compose.yml)
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null

# Cache & Queue (auto-detects Redis if REDIS_HOST is set)
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

# Session Configuration
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Sanctum Token Configuration
SANCTUM_TOKEN_EXPIRATION=1440

# MinIO Configuration (set automatically in Docker via docker-compose.yml)
MINIO_ENDPOINT=http://127.0.0.1:9002
MINIO_ACCESS_KEY=minioadmin
MINIO_SECRET_KEY=minioadmin123
MINIO_BUCKET=workforcehub
MINIO_REGION=us-east-1
MINIO_URL=http://localhost:9002
EOF
    fi
    echo "✅ .env file created"
else
    echo "✅ .env file already exists"
fi

# Build and start container
echo "🐳 Building Docker image (this may take a few minutes)..."
echo "   Note: If you encounter network timeout errors, check your internet connection"
echo "   or Docker proxy settings."
docker-compose build --no-cache

echo "🚀 Starting container..."
docker-compose up -d

# Wait for services to be ready
echo "⏳ Waiting for services to start (30 seconds)..."
sleep 30

# Install PHP dependencies
echo "📦 Installing PHP dependencies..."
docker-compose exec -T app composer install --no-interaction || {
    echo "⚠️  Composer install failed, trying again..."
    sleep 5
    docker-compose exec -T app composer install --no-interaction
}

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo "🔑 Generating application key..."
    docker-compose exec -T app php artisan key:generate
fi

# Run migrations
echo "🗄️  Running database migrations..."
docker-compose exec -T app php artisan migrate --force || {
    echo "⚠️  Migration failed, waiting a bit more for MySQL..."
    sleep 10
    docker-compose exec -T app php artisan migrate --force
}

# Install Node dependencies
echo "📦 Installing Node dependencies..."
docker-compose exec -T app npm install || {
    echo "⚠️  npm install failed, trying again..."
    docker-compose exec -T app npm install
}

# Build assets
echo "🎨 Building frontend assets..."
docker-compose exec -T app npm run production || {
    echo "⚠️  Asset build failed, trying development build..."
    docker-compose exec -T app npm run dev
}

# Set permissions
echo "🔐 Setting storage permissions..."
docker-compose exec -T app chmod -R 775 storage bootstrap/cache

# Ensure monitoring directories exist
echo "📊 Setting up monitoring directories..."
mkdir -p docker/prometheus/rules
mkdir -p docker/grafana/provisioning/datasources
mkdir -p docker/grafana/provisioning/dashboards
mkdir -p docker/grafana/dashboards
echo "✅ Monitoring directories ready"

# Setup MinIO
echo "📦 Setting up MinIO..."
# Check if MinIO is running (either as separate container or inside app container)
if docker-compose ps minio 2>/dev/null | grep -q "Up" || docker-compose exec -T app supervisorctl status minio 2>/dev/null | grep -q "RUNNING"; then
    echo "   MinIO is running, setting up bucket..."
    # Copy setup script to container and execute
    docker cp docker/setup-minio.sh app:/tmp/setup-minio.sh 2>/dev/null || {
        echo "   Creating setup script in container..."
        docker-compose exec -T app bash -c 'cat > /tmp/setup-minio.sh << "SCRIPTEOF"
#!/bin/bash
        MINIO_ENDPOINT="${MINIO_ENDPOINT:-http://127.0.0.1:9002}"
MINIO_ACCESS_KEY="${MINIO_ACCESS_KEY:-minioadmin}"
MINIO_SECRET_KEY="${MINIO_SECRET_KEY:-minioadmin123}"
MINIO_BUCKET="${MINIO_BUCKET:-workforcehub}"

echo "⏳ Waiting for MinIO to be ready..."
for i in {1..60}; do
    if curl -sf "${MINIO_ENDPOINT}/minio/health/live" > /dev/null 2>&1; then
        echo "✅ MinIO is ready!"
        break
    fi
    if [ $i -eq 60 ]; then
        echo "⚠️  MinIO did not become ready in time"
        exit 0
    fi
    sleep 1
done

# Try to create bucket using MinIO API
echo "📦 Creating bucket: $MINIO_BUCKET"
response=$(curl -s -w "\n%{http_code}" -X PUT \
    "${MINIO_ENDPOINT}/${MINIO_BUCKET}" \
    -H "x-amz-content-sha256: UNSIGNED-PAYLOAD" \
    --user "${MINIO_ACCESS_KEY}:${MINIO_SECRET_KEY}" 2>/dev/null || echo "000")

http_code=$(echo "$response" | tail -n1)
if [ "$http_code" = "200" ] || [ "$http_code" = "409" ]; then
    echo "✅ Bucket '\''$MINIO_BUCKET'\'' is ready"
else
    echo "⚠️  Could not create bucket automatically (HTTP $http_code)"
    echo "   You can create it manually via MinIO Console: http://localhost:9001"
fi
SCRIPTEOF
chmod +x /tmp/setup-minio.sh'
    }
    docker-compose exec -T app /tmp/setup-minio.sh || echo "⚠️  MinIO setup had issues, but continuing..."
else
    echo "⚠️  MinIO is not running. Waiting a bit more for it to start..."
    sleep 10
    # Try again with embedded MinIO
    if docker-compose exec -T app supervisorctl status minio 2>/dev/null | grep -q "RUNNING"; then
        echo "   MinIO is now running, setting up bucket..."
        docker-compose exec -T app /tmp/setup-minio.sh || echo "⚠️  MinIO setup had issues, but continuing..."
    else
        echo "⚠️  MinIO is still not running. Check supervisor logs:"
        echo "   docker-compose exec app supervisorctl tail -f minio"
        echo "   Or create bucket manually via MinIO Console: http://localhost:9001"
    fi
fi

# Wait for monitoring services to be ready
echo "📊 Waiting for monitoring services to start..."
sleep 10

# Check if curl is available
if command -v curl > /dev/null 2>&1; then
    # Check Prometheus
    echo "   Checking Prometheus..."
    for i in {1..30}; do
        if curl -sf http://localhost:9090/-/healthy > /dev/null 2>&1; then
            echo "✅ Prometheus is ready!"
            break
        fi
        if [ $i -eq 30 ]; then
            echo "⚠️  Prometheus did not become ready in time (check manually at http://localhost:9090)"
        fi
        sleep 1
    done

    # Check Grafana
    echo "   Checking Grafana..."
    for i in {1..30}; do
        if curl -sf http://localhost:3000/api/health > /dev/null 2>&1; then
            echo "✅ Grafana is ready!"
            break
        fi
        if [ $i -eq 30 ]; then
            echo "⚠️  Grafana did not become ready in time (check manually at http://localhost:3000)"
        fi
        sleep 1
    done

    # Verify metrics endpoint
    echo "   Verifying Laravel metrics endpoint..."
    for i in {1..20}; do
        if curl -sf http://localhost:8000/api/metrics > /dev/null 2>&1; then
            echo "✅ Metrics endpoint is accessible!"
            break
        fi
        if [ $i -eq 20 ]; then
            echo "⚠️  Metrics endpoint not ready yet (may need app restart)"
        fi
        sleep 1
    done
else
    echo "⚠️  curl not found, skipping health checks"
    echo "   Please verify monitoring services manually:"
    echo "   - Prometheus: http://localhost:9090"
    echo "   - Grafana: http://localhost:3000"
    echo "   - Metrics: http://localhost:8000/api/metrics"
fi

echo ""
echo "✅ Setup complete!"
echo ""
echo "🌐 Access your application at: http://localhost:8000"
echo "🗄️  MySQL is available on port 3306"
echo "📦 MinIO Console: http://localhost:9001"
echo "   Username: minioadmin"
echo "   Password: minioadmin123"
echo ""
echo "📊 Monitoring Services:"
echo "   📈 Grafana Dashboard: http://localhost:3000"
echo "      Username: admin"
echo "      Password: admin123"
echo "   📉 Prometheus: http://localhost:9090"
echo "   📊 Metrics Endpoint: http://localhost:8000/api/metrics"
echo ""
echo "Useful commands:"
echo "  - View logs: docker-compose logs -f"
echo "  - Stop container: docker-compose down"
echo "  - Restart container: docker-compose restart"
echo "  - Execute artisan: docker-compose exec app php artisan <command>"
echo "  - Access MySQL: docker-compose exec app mysql -u laravel_user -p employee_management"
echo ""
echo "📖 For monitoring documentation, see: docker/MONITORING.md"
echo ""

