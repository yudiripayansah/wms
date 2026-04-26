<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_barang',
        'brand',
        'barcode',
        'sku',
        'nama_barang',
        'colour',
        'size',
        'price',
    ];

    public function stocks()
    {
        return $this->hasMany(Stock::class, 'kode_barang', 'kode_barang');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'kode_barang', 'kode_barang');
    }
}
