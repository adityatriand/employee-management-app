<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'workspace_id',
        'model_type',
        'model_id',
        'action',
        'old_values',
        'new_values',
        'description',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who performed the action
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the workspace this activity log belongs to
     */
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the model that was affected
     */
    public function model()
    {
        return $this->morphTo();
    }

    /**
     * Get formatted action badge color
     */
    public function getActionColorAttribute()
    {
        return match($this->action) {
            'created' => 'success',
            'updated' => 'primary',
            'deleted' => 'danger',
            'restored' => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Get formatted action label
     */
    public function getActionLabelAttribute()
    {
        return match($this->action) {
            'created' => 'Dibuat',
            'updated' => 'Diupdate',
            'deleted' => 'Dihapus',
            'restored' => 'Dipulihkan',
            default => ucfirst($this->action),
        };
    }
}

