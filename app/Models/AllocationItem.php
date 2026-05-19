<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllocationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'allocation_id',
        'barcode',
        'qty',
        'location',
        'bin',
    ];

    public function allocation()
    {
        return $this->belongsTo(Allocation::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'barcode', 'barcode');
    }
}
