<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductImportRequest extends FormRequest
{
    /**
     * Tentukan apakah user diizinkan untuk membuat request ini.
     *
     * @return bool
     */
    public function authorize()
    {
        // Pastikan hanya Admin yang bisa mengimpor
        return true; 
    }

    /**
     * Dapatkan aturan validasi yang berlaku untuk request ini.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'excel_file' => [
                'required',
                'file',
                'mimes:xls,xlsx', // Hanya izinkan format file Excel
                'max:5120', // Maksimal ukuran file 5MB (5120 KB)
            ],
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
            'excel_file.required' => 'File Excel wajib diunggah.',
            'excel_file.mimes' => 'Format file harus .xls atau .xlsx.',
            'excel_file.max' => 'Ukuran file Excel maksimal 5MB.',
        ];
    }
}
