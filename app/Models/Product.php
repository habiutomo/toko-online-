<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    
    // Model ini terhubung dengan skema 'master'
    protected $table = 'master.products';

    // Kolom yang dapat diisi secara massal (mass assignable)
    protected $fillable = [
        'sku',
        'name',
        'description',
        'price',
        'stock',
    ];

    // Casting untuk tipe data yang tepat
    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    /**
     * Relasi: Satu Produk dapat memiliki banyak OrderItem (dipesan berkali-kali).
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }
}
