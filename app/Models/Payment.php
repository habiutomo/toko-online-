<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    
    // Model ini terhubung dengan skema 'transactions'
    protected $table = 'transactions.payments';
    
    // Kolom yang dapat diisi secara massal
    protected $fillable = [
        'order_id',          // Foreign Key ke tabel transactions.orders
        'proof_url',         // Path/URL tempat bukti pembayaran disimpan
        'verified_by_cs_l1', // Status verifikasi oleh CS Layer 1 (Boolean)
        'verification_time', // Waktu konfirmasi pembayaran
        'verified_by',       // ID User CS Layer 1 yang melakukan verifikasi (FK ke master.users)
    ];

    /**
     * Casting tipe data kolom:
     * verified_by_cs_l1 diubah menjadi boolean.
     * verification_time diubah menjadi objek datetime.
     */
    protected $casts = [
        'verified_by_cs_l1' => 'boolean',
        'verification_time' => 'datetime',
    ];

    /**
     * Relasi: Payment dimiliki oleh satu Order.
     */
    public function order()
    {
        // One-to-One: Setiap pembayaran terikat pada satu pesanan
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Relasi: Payment diverifikasi oleh satu User (CS L1).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function verifier()
    {
        // Foreign Key ke tabel users di skema 'master' (petugas CS L1)
        return $this->belongsTo(User::class, 'verified_by', 'id');
    }
}
