<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // Penting untuk membaca header kolom

class ProductsImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Pastikan nama header di file Excel sesuai dengan key array $row
        return new Product([
            'sku'         => $row['sku'],
            'name'        => $row['nama_barang'],
            'description' => $row['deskripsi'],
            'price'       => $row['harga'],
            'stock'       => $row['stok'],
        ]);
    }
}
