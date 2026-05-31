<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'synopsis' => ['required', 'string', 'min:10'],
            'year' => ['required', 'integer', 'between:1900,2100'],
            'duration_min' => ['nullable', 'integer', 'between:30,500'],
            'director' => ['nullable', 'string', 'max:120'],

            // Poster: salah satu wajib (file atau URL)
            'poster_file' => ['required_without:poster_url', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'poster_url' => ['required_without:poster_file', 'nullable', 'url', 'max:500'],

            // Backdrop: opsional, kalau ada salah satu mode bisa
            'backdrop_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'backdrop_url' => ['nullable', 'url', 'max:500'],

            // Genres
            'genres' => ['nullable', 'array'],
            'genres.*' => ['integer', 'exists:genres,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'poster_file.required_without' => 'Wajib mengunggah poster atau memberikan URL poster.',
            'poster_url.required_without' => 'Wajib mengunggah poster atau memberikan URL poster.',
        ];
    }
}
