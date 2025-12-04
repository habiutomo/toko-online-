<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductImportRequest;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel; // Memerlukan package Maatwebsite/Excel

class ProductImportController extends Controller
{
    /**
     * Tampilkan formulir untuk mengunggah file Excel.
     */
    public function create()
    {
        // Di sini Admin dapat melihat contoh format Excel yang benar (SKU, Name, Price, Stock, dll.)
        return view('admin.products.import');
    }

    /**
     * Proses unggah dan impor file Excel untuk penambahan barang massal.
     */
    public function store(ProductImportRequest $request)
    {
        // Pastikan file yang diunggah adalah file Excel yang valid (Validasi ada di ProductImportRequest)
        $file = $request->file('excel_file');
        
        try {
            // Gunakan class ProductsImport untuk memproses file
            // Note: ProductsImport.php akan menangani pemetaan kolom ke database.
            Excel::import(new ProductsImport, $file);
            
            return redirect()->route('admin.products.index')
                             ->with('success', 'Data barang massal berhasil diimpor dari file Excel.');

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            // Tangani error validasi data di dalam file Excel
            $failures = $e->failures();
            
            return back()->withFailures($failures)
                         ->with('error', 'Gagal mengimpor data. Terdapat kesalahan validasi di dalam file Excel.');
            
        } catch (\Exception $e) {
            // Tangani error umum (misalnya format file salah)
            return back()->with('error', 'Gagal mengimpor data: ' . $e->getMessage());
        }
    }
}
