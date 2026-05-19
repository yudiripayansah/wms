<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'barcode',
        'brand',
        'sku',
        'article',
        'color',
        'size',
    ];

    public function stocks()
    {
        return $this->hasMany(Stock::class, 'barcode', 'barcode');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'barcode', 'barcode');
    }
}
