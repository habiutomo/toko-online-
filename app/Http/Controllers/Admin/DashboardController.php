<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman utama (dashboard) Admin.
     */
    public function index()
    {
        // Data yang biasanya ditampilkan di dashboard:
        // 1. Statistik total produk
        // 2. Jumlah pesanan yang menunggu diproses (CS L1 & L2)
        // 3. Grafik penjualan
        
        $totalProducts = \App\Models\Product::count();
        $ordersWaitingVerification = \App\Models\Order::where('status', 'waiting_verification')->count();
        $ordersReadyToShip = \App\Models\Order::where('status', 'processed')->count();

        return view('admin.dashboard', compact('totalProducts', 'ordersWaitingVerification', 'ordersReadyToShip'));
    }
}
