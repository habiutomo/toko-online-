<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OrderService; // Menggunakan OrderService untuk logika pembatalan

class CancelPendingOrders extends Command
{
    /**
     * Nama dan signature dari console command.
     * Perintah ini akan dipanggil di Cron Job: php artisan orders:cancel-pending
     *
     * @var string
     */
    protected $signature = 'orders:cancel-pending';

    /**
     * Deskripsi console command.
     *
     * @var string
     */
    protected $description = 'Batalkan pesanan yang belum dibayar/diverifikasi setelah batas waktu 1x24 jam.';
    
    protected $orderService;

    /**
     * Buat instance command baru.
     * Inject OrderService melalui constructor.
     *
     * @param OrderService $orderService
     */
    public function __construct(OrderService $orderService)
    {
        parent::__construct();
        $this->orderService = $orderService;
    }

    /**
     * Jalankan console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Memulai pengecekan pesanan yang perlu dibatalkan secara otomatis...');
        
        try {
            // Memanggil metode di OrderService untuk menjalankan logika bisnis pembatalan
            $count = $this->orderService->autoCancelOldPendingOrders();
            
            $this->info("Pengecekan selesai. Sebanyak {$count} pesanan berhasil dibatalkan secara otomatis oleh sistem.");
        } catch (\Exception $e) {
            $this->error('Gagal menjalankan pembatalan otomatis: ' . $e->getMessage());
            return 1; // Mengindikasikan kegagalan
        }

        return 0; // Mengindikasikan sukses
    }
}
