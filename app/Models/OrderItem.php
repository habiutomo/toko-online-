<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;
    
    // Model ini terhubung dengan skema 'transactions'
    protected $table = 'transactions.order_items';
    
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    /**
     * Relasi: OrderItem dimiliki oleh satu Order.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Relasi: OrderItem merujuk ke satu Product.
     */
    public function product()
    {
        // Foreign key ke tabel products di skema 'master'
        return $this->belongsTo(Product::class, 'product_id');
    }
}
