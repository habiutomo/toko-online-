<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;

class ProductController extends Controller
{
    /**
     * Tampilkan daftar semua barang.
     */
    public function index()
    {
        $products = Product::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.products.index', compact('products'));
    }

    /**
     * Tampilkan formulir untuk membuat barang baru.
     */
    public function create()
    {
        return view('admin.products.create');
    }

    /**
     * Simpan barang baru ke database (Input Tunggal).
     */
    public function store(StoreProductRequest $request)
    {
        // Validasi dilakukan di StoreProductRequest
        Product::create($request->validated());

        return redirect()->route('admin.products.index')
                         ->with('success', 'Barang baru berhasil ditambahkan.');
    }

    /**
     * Tampilkan formulir untuk mengedit barang.
     */
    public function edit(Product $product)
    {
        return view('admin.products.edit', compact('product'));
    }

    /**
     * Perbarui data barang di database.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return redirect()->route('admin.products.index')
                         ->with('success', 'Data barang berhasil diperbarui.');
    }

    /**
     * Hapus barang dari database.
     */
    public function destroy(Product $product)
    {
        $product->delete();
        
        return redirect()->route('admin.products.index')
                         ->with('success', 'Barang berhasil dihapus.');
    }
}
