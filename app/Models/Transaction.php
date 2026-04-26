<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'session_id',
        'kode_barang',
        'qty',
        'location',
        'box',
        'status',
        'type',
        'remarks',
    ];
    public function product()
    {
        return $this->belongsTo(Product::class, 'kode_barang', 'kode_barang');
    }
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'kode_barang', 'kode_barang');
    }
}
