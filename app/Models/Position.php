<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use HasFactory;

    protected $table = 'jabatan';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get all employees for this position
     */
    public function employees()
    {
        return $this->hasMany(Employee::class, 'position_id', 'id');
    }
}

