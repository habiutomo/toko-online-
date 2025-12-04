<?php

namespace App\Http\Controllers\CustomerService;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Http\Requests\CS\UpdateShippingRequest; // Asumsi ada request validation untuk data pengiriman

class CS_Layer2Controller extends Controller
{
    /**
     * Tampilkan daftar pesanan yang siap diproses/dikemas.
     */
    public function processingQueue()
    {
        [cite_start]// Pesanan yang statusnya 'processed' berarti sudah dikonfirmasi oleh CS L1 [cite: 11]
        $orders = Order::where('status', 'processed')
                       ->with('user')
                       ->orderBy('updated_at', 'asc')
                       ->paginate(10);
                       
        [cite_start]// CS Layer 2 bertugas memastikan barang yang dipesan diproses dengan benar [cite: 12]
        return view('cs.layer2.processing_queue', compact('orders'));
    }

    /**
     * Tampilkan detail pesanan dan formulir untuk mencatat pengepakan.
     */
    public function showProcess(Order $order)
    {
        if ($order->status !== 'processed') {
            return redirect()->route('cs.l2.queue')->with('error', 'Pesanan tidak dalam antrian pemrosesan.');
        }

        return view('cs.layer2.show_process', compact('order'));
    }

    /**
     * Catat detail pengepakan dan perbarui status menjadi 'shipped' (terkirim).
     */
    public function updateShipping(UpdateShippingRequest $request, Order $order)
    {
        // Validasi input: nomor resi, kurir, dll.
        $validated = $request->validated();
        
        // Catat detail pengiriman (kurir, nomor resi)
        $order->update([
            'shipping_carrier' => $validated['carrier'],
            'tracking_number'  => $validated['tracking_number'],
            'status'           => 'shipped', // Status diubah dari 'processed' menjadi 'shipped'
        ]);
        
        [cite_start]// CS Layer 2 bertanggung jawab memantau status pengiriman hingga barang sampai ke pembeli [cite: 13]
        
        return redirect()->route('cs.l2.queue')
                         ->with('success', "Pesanan #{$order->order_number} telah dikemas dan dikirim. Status diubah menjadi 'Shipped'.");
    }

    /**
     * Tampilkan daftar pesanan yang sedang dalam proses pengiriman (monitoring).
     */
    public function monitoringQueue()
    {
        $orders = Order::where('status', 'shipped')
                       ->orderBy('updated_at', 'desc')
                       ->paginate(10);

        return view('cs.layer2.monitoring_queue', compact('orders'));
    }
    
    // Fitur Tambahan: Konfirmasi barang diterima
    public function confirmDelivered(Order $order)
    {
        // ... Logika untuk mengubah status menjadi 'delivered' ...
    }
}
