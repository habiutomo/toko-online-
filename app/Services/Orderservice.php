<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class OrderService
{
    /**
     * Mengonfirmasi pembayaran, mengurangi stok, dan meneruskan pesanan ke CS Layer 2.
     * Dipicu oleh CS Layer 1.
     *
     * @param Order $order
     * @param int $cs_l1_user_id ID pengguna CS Layer 1.
     * @return bool
     * @throws Exception Jika status pesanan tidak valid atau stok tidak mencukupi.
     */
    public function confirmPayment(Order $order, int $cs_l1_user_id): bool
    {
        // Stok hanya akan berkurang ketika pembayaran berhasil dilakukan dan diverifikasi oleh CS Layer 1.
        if ($order->status !== 'waiting_verification') {
            throw new Exception("Status pesanan tidak valid untuk konfirmasi. Status saat ini: {$order->status}");
        }

        // Gunakan TRANSAKSI DATABASE untuk memastikan operasi stok dan status bersifat atomic.
        DB::beginTransaction();
        try {
            // 1. PENGURANGAN STOK OTOMATIS
            foreach ($order->items as $item) {
                // Kunci baris produk untuk menghindari kondisi balapan (race condition)
                $product = Product::lockForUpdate()->find($item->product_id);

                if (!$product) {
                    throw new Exception("Produk ID: {$item->product_id} tidak ditemukan.");
                }

                // Cek ketersediaan stok
                if ($product->stock < $item->quantity) {
                    throw new Exception("Stok untuk produk '{$product->name}' tidak mencukupi.");
                }

                // Kurangi Stok dari tabel master.products
                $product->decrement('stock', $item->quantity);
            }

            // 2. UPDATE STATUS PEMBAYARAN & PESANAN
            $payment = $order->payment;
            $payment->verified_by_cs_l1 = true;
            $payment->verification_time = now();
            $payment->verified_by = $cs_l1_user_id;
            $payment->save();

            // Ubah status order menjadi 'processed' (diteruskan ke CS L2)
            $order->status = 'processed';
            $order->save();

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            // Lemparkan kembali exception
            throw $e;
        }
    }

    /**
     * Membatalkan pesanan (otomatis 1x24 jam atau manual CS L1/L2).
     *
     * @param Order $order
     * @return bool
     */
    public function cancelOrder(Order $order): bool
    {
        // Tidak perlu membatalkan yang sudah batal atau sudah dikirim/selesai
        if ($order->status === 'cancelled' || $order->status === 'shipped' || $order->status === 'delivered') {
            return false;
        }

        DB::beginTransaction();
        try {
            // Jika pesanan otomatis batal (1x24 jam), stok seharusnya belum berkurang (status: pending_payment/waiting_verification).
            // Stok HANYA dikembalikan jika statusnya sudah 'processed' atau lebih tinggi.
            if ($order->status === 'processed') {
                // 1. KEMBALIKAN STOK
                foreach ($order->items as $item) {
                    Product::find($item->product_id)->increment('stock', $item->quantity);
                }
            }

            // 2. UPDATE STATUS PESANAN
            $order->status = 'cancelled';
            $order->cancelled_at = now();
            $order->save();

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mengelola pembatalan massal untuk Scheduler (Cron Job).
     */
    public function autoCancelOldPendingOrders(): int
    {
        // Batas waktu: 1x24 jam
        $cutoff = Carbon::now()->subHours(24);

        $ordersToCancel = Order::whereIn('status', ['pending_payment', 'waiting_verification'])
                                ->where('created_at', '<', $cutoff)
                                ->get();

        $cancelledCount = 0;
        foreach ($ordersToCancel as $order) {
            try {
                // Panggil logika pembatalan untuk setiap pesanan. Stok kembali tersedia di stok.
                $this->cancelOrder($order);
                $cancelledCount++;
            } catch (Exception $e) {
                // Log error jika ada pesanan yang gagal dibatalkan.
                \Log::error("Gagal membatalkan pesanan otomatis #{$order->id}: " . $e->getMessage());
            }
        }

        return $cancelledCount;
    }
}
