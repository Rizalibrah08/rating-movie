<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMovieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        // Saat update, poster mungkin sudah ada di DB (poster_path atau poster_url existing).
        // Kita tidak wajibkan ulang, tapi kalau user mengirim poster_url baru/menghapus existing,
        // controller bertanggung jawab memastikan setidaknya satu mode tersedia.
        return [
            'title' => ['required', 'string', 'max:200'],
            'synopsis' => ['required', 'string', 'min:10'],
            'year' => ['required', 'integer', 'between:1900,2100'],
            'duration_min' => ['nullable', 'integer', 'between:30,500'],
            'director' => ['nullable', 'string', 'max:120'],

            'poster_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'poster_url' => ['nullable', 'url', 'max:500'],
            'backdrop_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'backdrop_url' => ['nullable', 'url', 'max:500'],

            // Boolean flags untuk hapus existing asset (dikirim dari Vue form)
            'remove_poster' => ['nullable', 'boolean'],
            'remove_backdrop' => ['nullable', 'boolean'],

            'genres' => ['nullable', 'array'],
            'genres.*' => ['integer', 'exists:genres,id'],
        ];
    }
}
