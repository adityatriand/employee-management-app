<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\MinioBucketService;

class Workspace extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'owner_id',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($workspace) {
            if (empty($workspace->slug)) {
                $workspace->slug = Str::slug($workspace->name);
            }
        });

        static::created(function ($workspace) {
            // Create MinIO bucket for this workspace
            try {
                $bucketService = new MinioBucketService();
                $bucketName = $bucketService->getBucketName($workspace->slug);
                $bucketService->createBucket($bucketName);
            } catch (\Exception $e) {
                Log::error("Error creating MinIO bucket for workspace '{$workspace->slug}': " . $e->getMessage());
            }
        });
    }

    /**
     * Get the owner of the workspace.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get all users in this workspace.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all employees in this workspace.
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get the logo URL.
     */
    public function getLogoUrlAttribute()
    {
        if ($this->logo) {
            try {
                // Use workspace slug in route - let the route handler check if file exists
                return route('workspace.storage.workspaces.logo', ['workspace' => $this->slug, 'workspace_id' => $this->id]);
            } catch (\Exception $e) {
                Log::warning('Workspace logo URL generation failed: ' . $e->getMessage());
            }
        }
        // Return default logo
        return asset('images/logo.png');
    }

    /**
     * Get storage disk configured for this workspace
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function getStorageDisk()
    {
        $bucketService = new MinioBucketService();
        $bucketName = $bucketService->getBucketName($this->slug);

        // Create a custom disk configuration for this workspace
        config([
            'filesystems.disks.minio_workspace' => [
                'driver' => 's3',
                'key' => config('filesystems.disks.minio.key'),
                'secret' => config('filesystems.disks.minio.secret'),
                'region' => config('filesystems.disks.minio.region', 'us-east-1'),
                'bucket' => $bucketName,
                'url' => config('filesystems.disks.minio.url'),
                'endpoint' => config('filesystems.disks.minio.endpoint'),
                'use_path_style_endpoint' => true,
                'throw' => false,
            ],
        ]);

        return Storage::disk('minio_workspace');
    }

    /**
     * Get bucket name for this workspace
     *
     * @return string
     */
    public function getBucketName()
    {
        $bucketService = new MinioBucketService();
        return $bucketService->getBucketName($this->slug);
    }

    /**
     * Check if user belongs to this workspace.
     */
    public function hasUser($userId)
    {
        return $this->users()->where('id', $userId)->exists();
    }
}

