<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $roles  Daftar peran yang diizinkan, dipisahkan koma (e.g., 'admin,cs_l1')
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        // 1. Cek apakah pengguna sudah login
        if (!Auth::check()) {
            // Jika belum login, redirect ke halaman login
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // 2. Ambil daftar peran yang diizinkan
        // Ubah string "admin,cs_l1" menjadi array ['admin', 'cs_l1']
        $allowedRoles = explode(',', $roles);

        // 3. Cek apakah peran pengguna ada di daftar peran yang diizinkan
        // Asumsi: Kolom 'role' ada di tabel master.users
        if (!in_array($user->role, $allowedRoles)) {
            // Jika peran tidak diizinkan (Unauthorized), tampilkan error 403 atau redirect
            
            // Log error
            \Log::warning('Akses ditolak: User ID ' . $user->id . ' dengan role "' . $user->role . '" mencoba mengakses rute yang memerlukan role: ' . $roles);

            // Redirect ke halaman dashboard user, atau tampilkan error 403
            return response()->view('errors.403', [], 403);
            // return redirect('/home')->with('error', 'Akses Ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        // 4. Lanjutkan request jika otorisasi berhasil
        return $next($request);
    }
}
