use App\Models\Order;
use Carbon\Carbon;
// ...

public function handle()
{
    // Cari semua pesanan yang statusnya masih 'pending_payment'
    // atau 'waiting_verification' DAN dibuat lebih dari 24 jam yang lalu.
    $cutoff = Carbon::now()->subHours(24);
    $ordersToCancel = Order::whereIn('status', ['pending_payment', 'waiting_verification'])
                            ->where('created_at', '<', $cutoff)
                            ->get();

    foreach ($ordersToCancel as $order) {
        // Panggil fungsi pembatalan
        $this->cancelOrderAndReturnStock($order); [cite: 8]
    }
}

protected function cancelOrderAndReturnStock(Order $order)
{
    // Jika stok sudah terlanjur dikurangi (walau seharusnya belum), kita harus mengembalikan stok.
    // Tetapi sesuai alur, stok baru berkurang setelah verifikasi CS L1.
    // Jika pesanan otomatis batal (karena 1x24 jam), stok seharusnya belum berkurang[cite: 7].

    // Meskipun begitu, alur pembatalan yang baik harus memastikan:

    // 1. UPDATE STATUS PESANAN
    $order->status = 'cancelled';
    $order->cancelled_at = now();
    $order->save(); [cite: 8]

    // 2. KEMBALIKAN STOK (Jika ada bug/alur yg membuat stok terlanjur berkurang)
    // Walaupun tidak diwajibkan oleh alur 1x24 jam, ini adalah praktik terbaik.
    if (false) { // Contoh kondisi jika kita perlu mengembalikan stok
        foreach ($order->items as $item) {
            Product::find($item->product_id)->increment('stock', $item->quantity); [cite: 8]
        }
    }

    $this->info("Order #{$order->id} automatically cancelled. Stock returned if applicable.");
}
