<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class File extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'file_type',
        'category',
        'description',
        'employee_id',
        'uploaded_by',
        'workspace_id',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    /**
     * Get the workspace that owns the file
     */
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the employee that owns the file
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Get the user who uploaded the file
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the file URL from MinIO via Laravel streaming endpoint
     */
    public function getUrlAttribute()
    {
        try {
            $disk = Storage::disk('minio');
            if ($disk->exists($this->file_path)) {
                // Use Laravel route to stream file instead of direct MinIO URL
                // Get workspace from employee or file's workspace_id
                $workspaceSlug = $this->workspace ? $this->workspace->slug : (auth()->user()->workspace ? auth()->user()->workspace->slug : '');
                if ($workspaceSlug) {
                    return route('workspace.storage.files', ['workspace' => $workspaceSlug, 'file' => $this->id]);
                }
                return '#';
            }
            return '#';
        } catch (\Exception $e) {
            // Fallback if MinIO is not configured
            Log::warning('File URL generation failed: ' . $e->getMessage());
            return '#';
        }
    }

    /**
     * Check if file exists in MinIO
     */
    public function getExistsAttribute()
    {
        try {
            return Storage::disk('minio')->exists($this->file_path);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the file size in human readable format
     */
    public function getFormattedSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get file icon based on mime type
     */
    public function getIconAttribute()
    {
        $mime = $this->mime_type;

        if (str_starts_with($mime, 'image/')) {
            return 'oi-image';
        } elseif (str_starts_with($mime, 'application/pdf')) {
            return 'oi-document';
        } elseif (in_array($mime, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) {
            return 'oi-document';
        } elseif (in_array($mime, ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])) {
            return 'oi-spreadsheet';
        } else {
            return 'oi-file';
        }
    }

    /**
     * Delete file from MinIO when model is deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($file) {
            // Only delete from storage if it's a force delete
            if ($file->isForceDeleting()) {
                Storage::disk('minio')->delete($file->file_path);
            }
        });
    }
}

