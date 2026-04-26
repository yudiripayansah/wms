<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllocationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'allocation_id',
        'kode_barang',
        'qty',
        'location',
        'box',
    ];

    public function allocation()
    {
        return $this->belongsTo(Allocation::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'kode_barang', 'kode_barang');
    }
}
