<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation; // Opsi: Validasi baris per baris
use Illuminate\Validation\Rule;

// Implementasikan ToModel (mengubah baris menjadi Model) dan WithHeadingRow (membaca header kolom)
class ProductsImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
    * @param array $row Data satu baris dari file Excel
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Pastikan nama header di file Excel (contoh: 'sku', 'nama_barang') sesuai dengan key array $row.
        // Data dimasukkan ke tabel master.products
        return new Product([
            'sku'         => $row['sku'],
            'name'        => $row['nama_barang'],
            'description' => $row['deskripsi'] ?? null, // Deskripsi opsional
            'price'       => $row['harga'],
            'stock'       => $row['stok'],
        ]);
    }
    
    /**
     * Tentukan aturan validasi untuk setiap baris di file Excel.
     * Ini penting untuk Impor Massal yang handal.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            // SKU harus ada, unik di tabel master.products, dan string
            'sku' => [
                'required',
                'string',
                Rule::unique('master.products', 'sku')
            ],
            'nama_barang' => 'required|string|max:255',
            'harga' => 'required|numeric|min:100',
            'stok' => 'required|integer|min:0',
        ];
    }
    
    /**
     * Tentukan baris mana yang dianggap sebagai header.
     * @return int
     */
    public function headingRow(): int
    {
        return 1;
    }
}
