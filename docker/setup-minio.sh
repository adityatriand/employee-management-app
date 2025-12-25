#!/bin/bash

# MinIO Setup Script
# This script waits for MinIO to be ready and creates the required bucket

set -e

MINIO_ENDPOINT="${MINIO_ENDPOINT:-http://minio:9000}"
MINIO_ACCESS_KEY="${MINIO_ACCESS_KEY:-minioadmin}"
MINIO_SECRET_KEY="${MINIO_SECRET_KEY:-minioadmin123}"
MINIO_BUCKET="${MINIO_BUCKET:-workforcehub}"

echo "🔧 Setting up MinIO bucket: $MINIO_BUCKET"

# Wait for MinIO to be ready (max 60 seconds)
echo "⏳ Waiting for MinIO to be ready..."
for i in {1..60}; do
    if curl -sf "${MINIO_ENDPOINT}/minio/health/live" > /dev/null 2>&1; then
        echo "✅ MinIO is ready!"
        break
    fi
    if [ $i -eq 60 ]; then
        echo "⚠️  MinIO did not become ready in time, but continuing..."
        exit 0
    fi
    echo "   Waiting... ($i/60)"
    sleep 1
done

# Install MinIO client (mc) if not available
if ! command -v mc &> /dev/null; then
    echo "📦 Installing MinIO client..."
    curl -o /tmp/mc https://dl.min.io/client/mc/release/linux-amd64/mc 2>/dev/null || \
    wget -O /tmp/mc https://dl.min.io/client/mc/release/linux-amd64/mc 2>/dev/null || {
        echo "⚠️  Could not download MinIO client, using API instead..."
        setup_minio_via_api
        exit 0
    }
    chmod +x /tmp/mc
    MC_BIN="/tmp/mc"
else
    MC_BIN="mc"
fi

# Configure MinIO alias
echo "🔗 Configuring MinIO connection..."
$MC_BIN alias set local "${MINIO_ENDPOINT}" "${MINIO_ACCESS_KEY}" "${MINIO_SECRET_KEY}" 2>/dev/null || {
    echo "⚠️  Could not configure MinIO client, trying API method..."
    setup_minio_via_api
    exit 0
}

# Check if bucket exists, create if not
echo "📦 Checking bucket: $MINIO_BUCKET"
if $MC_BIN ls local/"${MINIO_BUCKET}" > /dev/null 2>&1; then
    echo "✅ Bucket '$MINIO_BUCKET' already exists"
else
    echo "📦 Creating bucket: $MINIO_BUCKET"
    $MC_BIN mb local/"${MINIO_BUCKET}" 2>/dev/null || {
        echo "⚠️  Could not create bucket with mc, trying API method..."
        setup_minio_via_api
        exit 0
    }
    echo "✅ Bucket '$MINIO_BUCKET' created successfully"
fi

# Set bucket policy to public read (optional, for file access)
echo "🔐 Setting bucket policy..."
$MC_BIN anonymous set download local/"${MINIO_BUCKET}" 2>/dev/null || echo "⚠️  Could not set bucket policy (non-critical)"

echo "✅ MinIO setup complete!"
exit 0

# Function to setup MinIO via API (fallback method)
setup_minio_via_api() {
    echo "🌐 Setting up MinIO via API..."
    
    # Wait a bit more for MinIO API
    sleep 2
    
    # Create bucket using MinIO API
    response=$(curl -s -w "\n%{http_code}" -X PUT \
        "${MINIO_ENDPOINT}/${MINIO_BUCKET}" \
        -H "x-amz-content-sha256: UNSIGNED-PAYLOAD" \
        --user "${MINIO_ACCESS_KEY}:${MINIO_SECRET_KEY}" 2>/dev/null || echo "000")
    
    http_code=$(echo "$response" | tail -n1)
    
    if [ "$http_code" = "200" ] || [ "$http_code" = "409" ]; then
        echo "✅ Bucket '$MINIO_BUCKET' is ready (HTTP $http_code)"
    else
        echo "⚠️  Could not create bucket via API (HTTP $http_code), but continuing..."
        echo "   You may need to create the bucket manually via MinIO Console:"
        echo "   URL: http://localhost:9001"
        echo "   Username: $MINIO_ACCESS_KEY"
        echo "   Password: $MINIO_SECRET_KEY"
        echo "   Bucket name: $MINIO_BUCKET"
    fi
}

