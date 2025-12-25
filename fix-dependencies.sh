#!/bin/bash

echo "🔧 Fixing missing vendor/autoload.php error..."

echo "📦 Installing Composer dependencies..."
docker-compose exec -T app composer install --no-interaction || {
    echo "⚠️  First attempt failed, retrying..."
    sleep 3
    docker-compose exec -T app composer install --no-interaction
}

echo "📦 Installing Node dependencies..."
docker-compose exec -T app npm install || {
    echo "⚠️  First attempt failed, retrying..."
    sleep 3
    docker-compose exec -T app npm install
}

echo "🎨 Building frontend assets..."
docker-compose exec -T app npm run production || docker-compose exec -T app npm run dev

echo "🔑 Generating application key (if needed)..."
docker-compose exec -T app php artisan key:generate || echo "Key already exists"

echo "🗄️  Running migrations..."
docker-compose exec -T app php artisan migrate --force || {
    echo "⚠️  Waiting for MySQL..."
    sleep 5
    docker-compose exec -T app php artisan migrate --force
}

echo ""
echo "✅ Dependencies installed!"
echo "🌐 Refresh your browser at http://localhost:8000"
echo ""

