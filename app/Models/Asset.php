<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'assets';

    protected $fillable = [
        'name',
        'asset_tag',
        'description',
        'asset_type',
        'serial_number',
        'brand',
        'model',
        'purchase_date',
        'purchase_price',
        'current_value',
        'status',
        'current_location',
        'department',
        'warranty_expiry',
        'image',
        'notes',
        'assigned_to',
        'assigned_date',
        'assigned_by',
        'workspace_id',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'assigned_date' => 'date',
        'purchase_price' => 'decimal:2',
        'current_value' => 'decimal:2',
    ];

    protected $appends = ['image_url', 'status_color', 'status_label'];

    /**
     * Get the workspace that owns the asset
     */
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the employee currently assigned to this asset.
     */
    public function assignedEmployee()
    {
        return $this->belongsTo(Employee::class, 'assigned_to', 'id');
    }

    /**
     * Alias for assignedEmployee (for consistency)
     */
    public function assignedTo()
    {
        return $this->assignedEmployee();
    }

    /**
     * Get the user who assigned this asset.
     */
    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by', 'id');
    }

    /**
     * Get all assignment history for this asset.
     */
    public function assignments()
    {
        return $this->hasMany(AssetAssignment::class, 'asset_id', 'id');
    }

    /**
     * Get the image URL from MinIO via Laravel streaming endpoint or fallback to placeholder.
     */
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            try {
                // Get workspace slug for route generation
                $workspaceSlug = $this->workspace ? $this->workspace->slug : (auth()->check() && auth()->user()->workspace ? auth()->user()->workspace->slug : null);
                
                if ($workspaceSlug) {
                    // Use Laravel route to stream image - the route handler will check if file exists in workspace bucket
                    return route('workspace.storage.assets.image', ['workspace' => $workspaceSlug, 'asset' => $this->id]);
                }
                return '#';
            } catch (\Exception $e) {
                // Route generation might fail, fall through to placeholder
                \Log::warning('Asset image URL generation failed: ' . $e->getMessage());
            }
        }
        // Return data URI for placeholder
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50"><rect width="50" height="50" fill="#f1f5f9" stroke="#e2e8f0" stroke-width="1"/><rect x="10" y="10" width="30" height="24" fill="#cbd5e1" rx="2"/><circle cx="25" cy="22" r="6" fill="#94a3b8"/><path d="M10 30 Q10 25 25 25 Q40 25 40 30 L40 35 L10 35 Z" fill="#94a3b8"/></svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Get status color for badges.
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'available' => 'success',
            'assigned' => 'primary',
            'maintenance' => 'warning',
            'retired' => 'secondary',
            'lost' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get status label in Bahasa.
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'available' => 'Tersedia',
            'assigned' => 'Ditetapkan',
            'maintenance' => 'Perawatan',
            'retired' => 'Pensiun',
            'lost' => 'Hilang',
            default => $this->status,
        };
    }

    /**
     * Generate unique asset tag.
     */
    public static function generateAssetTag($prefix = 'AST')
    {
        $lastAsset = self::orderBy('id', 'desc')->first();
        $number = $lastAsset ? $lastAsset->id + 1 : 1;
        return $prefix . '-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}

