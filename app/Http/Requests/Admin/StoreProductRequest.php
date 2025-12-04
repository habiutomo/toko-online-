<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Tentukan apakah user diizinkan untuk membuat request ini.
     * Karena ini adalah fitur Admin, kita harus memastikan user memiliki role 'admin'.
     *
     * @return bool
     */
    public function authorize()
    {
        // Asumsi: Kita menggunakan RoleMiddleware atau pengecekan langsung
        // Jika Anda menggunakan RoleMiddleware di route, Anda bisa mengembalikan true.
        // Jika tidak, Anda bisa cek: return auth()->user()->role === 'admin';
        return true; 
    }

    /**
     * Dapatkan aturan validasi yang berlaku untuk request ini.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        // Validasi berdasarkan struktur kolom tabel 'products'
        return [
            // SKU harus unik di tabel master.products
            'sku' => ['required', 'string', 'max:50', 'unique:master.products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:100'], // Harga minimal 100
            'stock' => ['required', 'integer', 'min:0'], // Stok tidak boleh minus
        ];
    }

    /**
     * Kustomisasi pesan error validasi.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'sku.unique' => 'Kode SKU ini sudah digunakan. Mohon gunakan kode lain.',
            'price.numeric' => 'Harga harus berupa angka.',
            'stock.integer' => 'Stok harus berupa bilangan bulat.',
        ];
    }
}
