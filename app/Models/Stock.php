<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_barang',
        'qty',
        'location',
        'box',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'kode_barang', 'kode_barang');
    }
}
