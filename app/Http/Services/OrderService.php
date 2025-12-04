<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderService
{
    /**
     * Mengonfirmasi pembayaran, mengurangi stok, dan meneruskan pesanan ke CS Layer 2.
     *
     * Aksi ini dipicu oleh CS Layer 1 setelah memverifikasi bukti pembayaran.
     *
     * @param Order $order
     * @param int $cs_l1_user_id ID pengguna CS Layer 1 yang melakukan verifikasi.
     * @return bool
     * @throws \Exception Jika status pesanan tidak valid atau stok tidak mencukupi.
     */
    public function confirmPayment(Order $order, int $cs_l1_user_id): bool
    {
        [cite_start]// Stok hanya akan berkurang ketika pembayaran berhasil dilakukan dan diverifikasi oleh CS Layer 1[cite: 7].
        if ($order->status !== 'waiting_verification') {
            throw new \Exception("Status pesanan tidak valid untuk konfirmasi. Status saat ini: {$order->status}");
        }

        // Gunakan transaksi database untuk memastikan pengurangan stok dan update status berjalan atomic.
        // Jika salah satu gagal, semua di-rollback.
        DB::beginTransaction();
        try {
            // 1. PENGURANGAN STOK OTOMATIS
            foreach ($order->items as $item) {
                // Kunci baris produk untuk menghindari kondisi balapan (race condition)
                $product = Product::lockForUpdate()->find($item->product_id);

                if (!$product) {
                    throw new \Exception("Produk ID: {$item->product_id} tidak ditemukan.");
                }

                // Cek ketersediaan stok terakhir (Penting!)
                if ($product->stock < $item->quantity) {
                    throw new \Exception("Stok untuk produk '{$product->name}' tidak mencukupi. Tersedia: {$product->stock}, Diminta: {$item->quantity}");
                }

                // Kurangi Stok dari tabel products
                $product->decrement('stock', $item->quantity); // Ini mengeksekusi: product.stock = product.stock - item.quantity
            }
            [cite_start]// Setiap pembelian yang berhasil akan otomatis mengurangi stok barang yang ada di sistem[cite: 5].

            // 2. UPDATE STATUS PEMBAYARAN & PESANAN
            $payment = $order->payment;
            $payment->verified_by_cs_l1 = true;
            $payment->verification_time = now();
            $payment->verified_by = $cs_l1_user_id; // Catat siapa yang verifikasi
            $payment->save();

            // Ubah status order menjadi 'processed' (diteruskan ke CS L2)
            $order->status = 'processed';
            $order->save();

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            // Lemparkan kembali exception untuk ditangani di Controller
            throw $e;
        }
    }

    /**
     * Membatalkan pesanan (baik secara otomatis setelah 1x24 jam atau manual oleh CS L1).
     *
     * @param Order $order
     * @return bool
     */
    public function cancelOrder(Order $order): bool
    {
        // Status pesanan yang bisa dibatalkan: pending_payment, waiting_verification, (atau processed jika ada kasus khusus)
        if ($order->status === 'cancelled') {
            return false; // Sudah dibatalkan
        }

        DB::beginTransaction();
        try {
            // Jika pesanan otomatis dibatalkan, stok seharusnya belum berkurang.
            // Namun, kita tetap harus memastikan stok dikembalikan jika statusnya sudah 'processed' atau ada bug.

            if ($order->status !== 'pending_payment' && $order->status !== 'waiting_verification') {
                // 1. KEMBALIKAN STOK (Hanya untuk kasus pembatalan di tahap yang stok sudah terpotong)
                // Catatan: Sesuai alur, ini tidak perlu untuk pembatalan 1x24 jam, tetapi penting untuk pembatalan manual
                // yang mungkin dilakukan setelah konfirmasi (misalnya, pembayaran ternyata ditarik kembali).
                foreach ($order->items as $item) {
                    Product::find($item->product_id)->increment('stock', $item->quantity);
                }
            }

            // 2. UPDATE STATUS PESANAN
            $order->status = 'cancelled';
            $order->cancelled_at = now();
            $order->save();

            // Jika pembeli tidak melakukan pembayaran atau pembayaran gagal dikonfirmasi oleh CS Layer 1 dalam waktu maksimal 1x24 jam, 
            [cite_start]// pesanan akan otomatis dibatalkan oleh sistem, dan barang akan kembali tersedia di stok[cite: 8].

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mengelola pembatalan massal untuk Scheduler (Cron Job).
     * Dijalankan oleh app/Console/Commands/CancelPendingOrders.php
     *
     * @return int Jumlah pesanan yang dibatalkan.
     */
    public function autoCancelOldPendingOrders(): int
    {
        $cutoff = Carbon::now()->subHours(24);

        $ordersToCancel = Order::whereIn('status', ['pending_payment', 'waiting_verification'])
                                ->where('created_at', '<', $cutoff)
                                ->get();

        $cancelledCount = 0;
        foreach ($ordersToCancel as $order) {
            try {
                // Panggil logika pembatalan untuk setiap pesanan.
                $this->cancelOrder($order);
                $cancelledCount++;
            } catch (\Exception $e) {
                // Log error jika ada pesanan yang gagal dibatalkan.
                \Log::error("Gagal membatalkan pesanan otomatis #{$order->id}: " . $e->getMessage());
            }
        }

        return $cancelledCount;
    }
}
