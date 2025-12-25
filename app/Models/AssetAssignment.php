<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetAssignment extends Model
{
    use HasFactory;

    protected $table = 'asset_assignments';

    protected $fillable = [
        'asset_id',
        'employee_id',
        'assigned_by',
        'assigned_at',
        'returned_at',
        'returned_by',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'date',
        'returned_at' => 'date',
    ];

    /**
     * Get the asset for this assignment.
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id', 'id');
    }

    /**
     * Get the employee for this assignment.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    /**
     * Get the user who assigned this asset.
     */
    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by', 'id');
    }

    /**
     * Get the user who returned this asset.
     */
    public function returner()
    {
        return $this->belongsTo(User::class, 'returned_by', 'id');
    }
}

