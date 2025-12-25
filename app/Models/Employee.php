<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'pegawai';
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
     * Get the photo URL
     */
    public function getPhotoUrlAttribute()
    {
        if ($this->photo && file_exists(public_path('images/' . $this->photo))) {
            return asset('images/' . $this->photo);
        }
        // Return data URI for placeholder instead of external URL
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60"><rect width="60" height="60" fill="#f1f5f9" stroke="#e2e8f0" stroke-width="1"/><circle cx="30" cy="22" r="8" fill="#cbd5e1"/><path d="M15 50 Q15 40 30 40 Q45 40 45 50" fill="#cbd5e1"/></svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}

