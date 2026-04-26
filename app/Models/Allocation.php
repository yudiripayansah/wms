<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Allocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'status',
        'remarks',
    ];

    public function items()
    {
        return $this->hasMany(AllocationItem::class);
    }
}
