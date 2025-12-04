<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Cart; // Asumsikan Anda memiliki model Cart
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * Tampilkan halaman keranjang belanja pengguna.
     */
    public function index()
    {
        $cartItems = Auth::user()->cartItems; // Asumsikan relasi di model User

        // Hitung total harga keranjang
        $cartTotal = $cartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        return view('shop.cart.index', compact('cartItems', 'cartTotal'));
    }

    /**
     * Tambahkan barang ke keranjang.
     * Stok tidak akan langsung berkurang. 
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:master.products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $productId = $request->input('product_id');
        $quantity = $request->input('quantity');
        $userId = Auth::id();

        // Cari item yang sudah ada di keranjang untuk produk ini
        $cartItem = Cart::where('user_id', $userId)
                        ->where('product_id', $productId)
                        ->first();

        if ($cartItem) {
            // Jika sudah ada, tambahkan kuantitas
            $cartItem->quantity += $quantity;
            $cartItem->save();
        } else {
            // Jika belum ada, buat item baru di keranjang
            Cart::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);
        }

        return redirect()->route('cart.index')
                         ->with('success', 'Barang berhasil ditambahkan ke keranjang.');
    }

    /**
     * Perbarui kuantitas barang di keranjang.
     */
    public function update(Request $request, Cart $cartItem)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        // Pastikan item keranjang milik user yang sedang login
        if ($cartItem->user_id !== Auth::id()) {
            abort(403);
        }

        $cartItem->quantity = $request->input('quantity');
        $cartItem->save();

        return redirect()->route('cart.index')
                         ->with('success', 'Kuantitas keranjang berhasil diperbarui.');
    }

    /**
     * Hapus barang dari keranjang.
     */
    public function remove(Cart $cartItem)
    {
        // Pastikan item keranjang milik user yang sedang login
        if ($cartItem->user_id !== Auth::id()) {
            abort(403);
        }

        $cartItem->delete();

        return redirect()->route('cart.index')
                         ->with('success', 'Barang berhasil dihapus dari keranjang.');
    }
}
