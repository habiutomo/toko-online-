<?php

namespace App\Http\Controllers\CustomerService;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Services\OrderService; // Menggunakan Service Class untuk Logika Bisnis
use Illuminate\Support\Facades\Auth;

class CS_Layer1Controller extends Controller
{
    protected $orderService;

    // Injeksi OrderService
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Tampilkan daftar pesanan yang menunggu verifikasi pembayaran.
     */
    public function verificationQueue()
    {
        // Cari pesanan yang sudah mengunggah bukti bayar tetapi belum diverifikasi
        $orders = Order::where('status', 'waiting_verification')
                       ->with('user', 'payment')
                       ->orderBy('created_at', 'asc')
                       ->paginate(10);

        return view('cs.layer1.verification_queue', compact('orders'));
    }

    /**
     * Tampilkan detail pesanan, termasuk bukti pembayaran.
     */
    public function showVerification(Order $order)
    {
        if ($order->status !== 'waiting_verification') {
            return redirect()->route('cs.l1.queue')->with('error', 'Pesanan tidak lagi menunggu verifikasi.');
        }

        [cite_start]// Tampilkan bukti pembayaran yang diunggah oleh pembeli [cite: 9]
        return view('cs.layer1.show_verification', compact('order'));
    }

    /**
     * Konfirmasi pembayaran, kurangi stok, dan teruskan ke CS Layer 2.
     */
    public function confirmPayment(Order $order)
    {
        $cs_l1_user_id = Auth::id();

        try {
            // Panggil Service Class yang berisi logika transaksi dan pengurangan stok
            $this->orderService->confirmPayment($order, $cs_l1_user_id);
            
            [cite_start]// Stok hanya akan berkurang ketika pembayaran berhasil dilakukan dan diverifikasi oleh CS Layer 1 [cite: 7]
            [cite_start]// CS Layer 1 mengonfirmasi pembelian dan pembayaran kepada pembeli [cite: 10]
            [cite_start]// Pesanan diteruskan ke Customer service Layer 2 untuk proses pengepakan dan pengiriman [cite: 11]

            return redirect()->route('cs.l1.queue')
                             ->with('success', "Pembayaran untuk Pesanan #{$order->order_number} berhasil dikonfirmasi. Stok telah dikurangi dan pesanan diteruskan ke CS Layer 2.");
        } catch (\Exception $e) {
            // Tangani jika terjadi kegagalan, misalnya stok tiba-tiba tidak cukup.
            return back()->with('error', 'Gagal memproses konfirmasi: ' . $e->getMessage());
        }
    }

    /**
     * Batalkan pesanan secara manual (misalnya bukti pembayaran tidak valid).
     */
    public function cancelOrder(Order $order)
    {
        // Panggil Service Class untuk membatalkan dan mengembalikan stok jika perlu
        $this->orderService->cancelOrder($order);

        return redirect()->route('cs.l1.queue')
                         ->with('success', "Pesanan #{$order->order_number} berhasil dibatalkan.");
    }
}
