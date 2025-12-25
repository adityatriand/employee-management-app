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
        'workspace_id',
        'user_id',
        'email', // For creating user account
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    /**
     * Get the workspace that owns the employee
     */
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the user account linked to this employee
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

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
     * Get all assets currently assigned to the employee.
     */
    public function assets()
    {
        return $this->hasMany(Asset::class, 'assigned_to', 'id');
    }

    /**
     * Get all asset assignment history for the employee.
     */
    public function assetAssignments()
    {
        return $this->hasMany(AssetAssignment::class, 'employee_id', 'id');
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
     * Get the photo URL (from MinIO via Laravel streaming endpoint or fallback to local)
     */
    public function getPhotoUrlAttribute()
    {
        // Get workspace slug for route generation
        $workspaceSlug = $this->workspace ? $this->workspace->slug : (auth()->check() && auth()->user()->workspace ? auth()->user()->workspace->slug : null);
        
        if (!$workspaceSlug) {
            // Return placeholder if no workspace
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60"><rect width="60" height="60" fill="#f1f5f9" stroke="#e2e8f0" stroke-width="1"/><circle cx="30" cy="22" r="8" fill="#cbd5e1"/><path d="M15 50 Q15 40 30 40 Q45 40 45 50" fill="#cbd5e1"/></svg>';
            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        }

        // First, try to get photo from MinIO via File relationship
        $photoFile = $this->photoFile;
        if ($photoFile) {
            try {
                // Use employee photo route instead of file route
                return route('workspace.storage.employees.photo', ['workspace' => $workspaceSlug, 'employee' => $this->id]);
            } catch (\Exception $e) {
                // Fallback if route generation fails
            }
        }

        // Check if photo field contains a file ID (new format)
        if ($this->photo && is_numeric($this->photo)) {
            $file = \App\Models\File::find($this->photo);
            if ($file && $file->file_type === 'photo') {
                try {
                    // Use employee photo route instead of file route
                    return route('workspace.storage.employees.photo', ['workspace' => $workspaceSlug, 'employee' => $this->id]);
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

