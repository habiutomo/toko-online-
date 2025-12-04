<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Tampilkan halaman profil pengguna.
     */
    public function index()
    {
        $user = Auth::user();
        return view('shop.profile.index', compact('user'));
    }

    /**
     * Tampilkan daftar riwayat pesanan pembeli.
     */
    public function orders()
    {
        // Tampilkan semua pesanan pengguna, diurutkan berdasarkan yang terbaru
        $orders = Order::where('user_id', Auth::id())
                       ->with('items.product', 'payment')
                       ->orderBy('created_at', 'desc')
                       ->paginate(10);
                       
        // Melalui halaman ini, pembeli dapat memantau Status Pesanan mereka:
        // pending_payment -> waiting_verification -> processed -> shipped -> cancelled
        return view('shop.profile.orders', compact('orders'));
    }

    /**
     * Tampilkan detail spesifik satu pesanan.
     */
    public function showOrder(Order $order)
    {
        // Pastikan user adalah pemilik sah pesanan
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        return view('shop.profile.show_order', compact('order'));
    }

    // Metode lain (misalnya updateProfile, updatePassword) dapat ditambahkan di sini.
}
