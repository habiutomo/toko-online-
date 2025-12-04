<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Proses checkout: Konversi Cart menjadi Order.
     */
    public function checkout(Request $request)
    {
        $user = Auth::user();
        $cartItems = $user->cartItems; // Mendapatkan item dari CartController

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Keranjang Anda kosong.');
        }

        DB::beginTransaction();
        try {
            // Hitung total harga
            $totalPrice = $cartItems->sum(function ($item) {
                return $item->product->price * $item->quantity;
            });
            
            // 1. Buat Order Baru
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => 'ORD-' . time() . '-' . $user->id, // Contoh nomor pesanan
                'total_price' => $totalPrice,
                'status' => 'pending_payment', // Status awal: Menunggu pembayaran
            ]);

            // 2. Pindahkan Item dari Keranjang ke OrderItems
            foreach ($cartItems as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->product->price, // Harga saat checkout
                ]);
            }

            // 3. Kosongkan Keranjang
            Cart::where('user_id', $user->id)->delete();

            DB::commit();

            return redirect()->route('order.show_payment', $order)
                             ->with('success', 'Pesanan berhasil dibuat. Mohon segera lakukan pembayaran.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses checkout. Silakan coba lagi.');
        }
    }
    
    /**
     * Tampilkan halaman untuk mengunggah bukti pembayaran.
     */
    public function showPayment(Order $order)
    {
        // Pastikan hanya user pemilik order yang dapat mengakses
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        if ($order->status !== 'pending_payment') {
            return redirect()->route('profile.orders')->with('error', 'Pesanan ini sudah tidak membutuhkan pembayaran.');
        }

        // Pembayaran dilakukan dengan cara mengunggah bukti pembayaran melalui formulir
        return view('shop.order.upload_payment', compact('order'));
    }

    /**
     * Unggah bukti pembayaran dan ubah status pesanan.
     */
    public function uploadPayment(Request $request, Order $order)
    {
        if ($order->user_id !== Auth::id() || $order->status !== 'pending_payment') {
            abort(403);
        }

        $request->validate([
            'payment_proof' => 'required|image|max:2048', // Maks 2MB, format gambar
        ]);
        
        // 1. Simpan Bukti Pembayaran
        $path = $request->file('payment_proof')->store('proofs', 'public');
        
        // 2. Catat data pembayaran di tabel payments
        Payment::create([
            'order_id' => $order->id,
            'proof_url' => $path,
            'verified_by_cs_l1' => false, // Default: Belum diverifikasi
        ]);

        // 3. Update Status Order
        $order->status = 'waiting_verification'; // Menunggu CS L1 memverifikasi
        $order->save();

        return redirect()->route('profile.orders')
                         ->with('success', 'Bukti pembayaran berhasil diunggah. Pesanan Anda kini menunggu verifikasi oleh Customer Service Layer 1 (maksimal 1x24 jam).');
    }
}
