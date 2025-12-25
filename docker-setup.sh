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

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=employee_management
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_password
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

# Setup MinIO
echo "📦 Setting up MinIO..."
if docker-compose ps minio 2>/dev/null | grep -q "Up"; then
    echo "   MinIO container is running, setting up bucket..."
    # Copy setup script to container and execute
    docker cp docker/setup-minio.sh app:/tmp/setup-minio.sh 2>/dev/null || {
        echo "   Creating setup script in container..."
        docker-compose exec -T app bash -c 'cat > /tmp/setup-minio.sh << "SCRIPTEOF"
#!/bin/bash
MINIO_ENDPOINT="${MINIO_ENDPOINT:-http://minio:9000}"
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
    echo "⚠️  MinIO container is not running. Start it with: docker-compose up -d minio"
    echo "   Then create bucket manually via MinIO Console: http://localhost:9001"
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
echo "Useful commands:"
echo "  - View logs: docker-compose logs -f"
echo "  - Stop container: docker-compose down"
echo "  - Restart container: docker-compose restart"
echo "  - Execute artisan: docker-compose exec app php artisan <command>"
echo "  - Access MySQL: docker-compose exec app mysql -u laravel_user -p employee_management"
echo ""

