use App\Models\Product;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

public function confirmPayment(Order $order, int $cs_l1_user_id)
{
    // Pastikan pesanan berada di status yang benar
    if ($order->status !== 'waiting_verification') {
        throw new \Exception("Order status is not valid for confirmation.");
    }

    // Menggunakan transaksi database untuk memastikan pengurangan stok dan update status
    // berjalan Atomic (berhasil semua atau gagal semua).
    DB::transaction(function () use ($order, $cs_l1_user_id) {
        // 1. PENGURANGAN STOK OTOMATIS
        foreach ($order->items as $item) {
            $product = Product::find($item->product_id);

            // Cek ketersediaan stok terakhir (Penting!)
            if ($product->stock < $item->quantity) {
                // Jika stok tiba-tiba kurang (misalnya dibeli user lain), batalkan transaksi.
                throw new \Exception("Stock for " . $product->name . " is insufficient.");
            }

            // Kurangi Stok dari tabel products (schema master)
            $product->decrement('stock', $item->quantity); [cite: 5, 7]
        }

        // 2. UPDATE STATUS PESANAN
        // Ubah status order menjadi 'paid' atau 'processed'
        $order->status = 'processed';
        $order->save();

        // 3. Catat verifikasi pembayaran oleh CS Layer 1
        $payment = $order->payment;
        $payment->verified_by_cs_l1 = true;
        $payment->verification_time = now();
        $payment->save();

        // 4. Trigger notifikasi/aksi selanjutnya (ke CS Layer 2)
        // ... (Kirim notifikasi ke CS Layer 2 untuk pengepakan) ...
    });
}
