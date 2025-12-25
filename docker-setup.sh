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

echo ""
echo "✅ Setup complete!"
echo ""
echo "🌐 Access your application at: http://localhost:8000"
echo "🗄️  MySQL is available on port 3306"
echo ""
echo "Useful commands:"
echo "  - View logs: docker-compose logs -f"
echo "  - Stop container: docker-compose down"
echo "  - Restart container: docker-compose restart"
echo "  - Execute artisan: docker-compose exec app php artisan <command>"
echo "  - Access MySQL: docker-compose exec app mysql -u laravel_user -p employee_management"
echo ""

