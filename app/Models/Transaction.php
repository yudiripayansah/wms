<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'session_id',
        'barcode',
        'qty',
        'location',
        'bin',
        'status',
        'type',
        'remarks',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'barcode', 'barcode');
    }
}
