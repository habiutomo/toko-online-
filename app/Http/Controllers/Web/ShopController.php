<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ShopController extends Controller
{
    /**
     * Tampilkan semua produk yang memiliki stok > 0.
     */
    public function index(Request $request)
    {
        // Pembeli dapat melihat dan membeli barang apa pun yang tersedia di toko online tanpa batasan kategori.
        $products = Product::where('stock', '>', 0)
                           ->orderBy('name', 'asc')
                           ->paginate(12); // Tampilkan 12 produk per halaman

        return view('shop.index', compact('products'));
    }

    /**
     * Tampilkan detail satu produk.
     */
    public function show(Product $product)
    {
        // Pastikan produk memiliki stok untuk ditampilkan
        if ($product->stock <= 0) {
            return redirect()->route('shop.index')->with('error', 'Produk tidak tersedia saat ini.');
        }

        return view('shop.show', compact('product'));
    }
}
