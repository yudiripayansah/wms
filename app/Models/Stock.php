<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'barcode',
        'qty',
        'location',
        'bin',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'barcode', 'barcode');
    }
}
