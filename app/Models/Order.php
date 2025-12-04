<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    
    // Model ini terhubung dengan skema 'transactions'
    protected $table = 'transactions.orders';

    protected $fillable = [
        'user_id',
        'order_number',
        'total_price',
        'status', // pending_payment, waiting_verification, processed, shipped, cancelled
        'cancelled_at',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Relasi: Satu Order dimiliki oleh satu User (Pembeli) di skema master.
     */
    public function user()
    {
        // Foreign key ke tabel users di skema 'master'
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi: Satu Order memiliki banyak OrderItem (barang yang dipesan).
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Relasi: Satu Order memiliki satu Payment (Bukti Pembayaran).
     */
    public function payment()
    {
        return $this->hasOne(Payment::class, 'order_id');
    }
}
