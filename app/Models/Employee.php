<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employees';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'gender',
        'birth_date',
        'photo',
        'description',
        'position_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    /**
     * Get the position that owns the employee
     */
    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id', 'id');
    }

    /**
     * Get all files associated with this employee
     */
    public function files()
    {
        return $this->hasMany(File::class, 'employee_id');
    }

    /**
     * Get employee photo file
     */
    public function photoFile()
    {
        return $this->hasOne(File::class, 'employee_id')
            ->where('file_type', 'photo')
            ->latest();
    }

    /**
     * Get the photo URL (from MinIO or fallback to local)
     */
    public function getPhotoUrlAttribute()
    {
        // First, try to get photo from MinIO via File relationship
        $photoFile = $this->photoFile;
        if ($photoFile) {
            try {
                if ($photoFile->exists) {
                    return $photoFile->url;
                }
            } catch (\Exception $e) {
                // Fallback if MinIO is not available
            }
        }

        // Check if photo field contains a file ID (new format)
        if ($this->photo && is_numeric($this->photo)) {
            $file = \App\Models\File::find($this->photo);
            if ($file && $file->file_type === 'photo') {
                try {
                    if ($file->exists) {
                        return $file->url;
                    }
                } catch (\Exception $e) {
                    // Fallback
                }
            }
        }

        // Fallback to local storage (for backward compatibility)
        if ($this->photo && file_exists(public_path('images/' . $this->photo))) {
            return asset('images/' . $this->photo);
        }

        // Return data URI for placeholder
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60"><rect width="60" height="60" fill="#f1f5f9" stroke="#e2e8f0" stroke-width="1"/><circle cx="30" cy="22" r="8" fill="#cbd5e1"/><path d="M15 50 Q15 40 30 40 Q45 40 45 50" fill="#cbd5e1"/></svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}

