<?php

namespace App\Services;

use Aws\S3\S3Client;
use Illuminate\Support\Facades\Log;
use Exception;

class MinioBucketService
{
    protected $s3Client;
    protected $endpoint;
    protected $accessKey;
    protected $secretKey;
    protected $region;

    public function __construct()
    {
        $this->endpoint = config('filesystems.disks.minio.endpoint');
        $this->accessKey = config('filesystems.disks.minio.key');
        $this->secretKey = config('filesystems.disks.minio.secret');
        $this->region = config('filesystems.disks.minio.region', 'us-east-1');

        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region' => $this->region,
            'endpoint' => $this->endpoint,
            'credentials' => [
                'key' => $this->accessKey,
                'secret' => $this->secretKey,
            ],
            'use_path_style_endpoint' => true,
        ]);
    }

    /**
     * Create a bucket for a workspace
     *
     * @param string $bucketName
     * @return bool
     */
    public function createBucket($bucketName)
    {
        try {
            // Check if bucket already exists
            if ($this->bucketExists($bucketName)) {
                Log::info("Bucket '{$bucketName}' already exists");
                return true;
            }

            // Create the bucket
            $this->s3Client->createBucket([
                'Bucket' => $bucketName,
            ]);

            Log::info("Bucket '{$bucketName}' created successfully");
            return true;
        } catch (Exception $e) {
            Log::error("Failed to create bucket '{$bucketName}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a bucket exists
     *
     * @param string $bucketName
     * @return bool
     */
    public function bucketExists($bucketName)
    {
        try {
            return $this->s3Client->doesBucketExist($bucketName);
        } catch (Exception $e) {
            Log::error("Failed to check bucket existence '{$bucketName}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a bucket (use with caution)
     *
     * @param string $bucketName
     * @return bool
     */
    public function deleteBucket($bucketName)
    {
        try {
            // First, delete all objects in the bucket
            $objects = $this->s3Client->listObjectsV2(['Bucket' => $bucketName]);
            if (isset($objects['Contents'])) {
                foreach ($objects['Contents'] as $object) {
                    $this->s3Client->deleteObject([
                        'Bucket' => $bucketName,
                        'Key' => $object['Key'],
                    ]);
                }
            }

            // Then delete the bucket
            $this->s3Client->deleteBucket(['Bucket' => $bucketName]);
            Log::info("Bucket '{$bucketName}' deleted successfully");
            return true;
        } catch (Exception $e) {
            Log::error("Failed to delete bucket '{$bucketName}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get bucket name for a workspace
     *
     * @param string $workspaceSlug
     * @return string
     */
    public function getBucketName($workspaceSlug)
    {
        // Use workspace slug as bucket name, prefixed with base bucket name
        $baseBucket = config('filesystems.disks.minio.bucket', 'workforcehub');
        return $baseBucket . '-' . $workspaceSlug;
    }

    /**
     * Get S3Client instance for custom operations
     *
     * @return S3Client
     */
    public function getS3Client()
    {
        return $this->s3Client;
    }
}

