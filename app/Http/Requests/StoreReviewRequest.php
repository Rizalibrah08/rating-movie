<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'movie_id' => ['required', 'integer', 'exists:movies,id'],
            'rating' => ['required', 'integer', 'between:0,100'],
            'body' => ['required', 'string', 'min:1', 'max:5000'],

            // Honeypot — field ini SELALU harus kosong. Bot otomatis biasanya isi
            // semua field yang ditemukan; manusia tidak bisa lihat field ini (display:none).
            'website' => ['nullable', 'string', 'size:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.between' => 'Skor harus antara 0 sampai 100.',
            'body.required' => 'Ulasan tidak boleh kosong.',
            'website.size' => 'Form tidak valid.',
        ];
    }

    /**
     * Tangani gagal validasi pada honeypot dengan diam-diam log + response generic.
     */
    protected function failedValidation(Validator $validator): void
    {
        if ($validator->errors()->has('website')) {
            // Bot detected. Log singkat tanpa membocorkan deteksi.
            logger()->warning('Honeypot triggered on review submit', [
                'user_id' => $this->user()?->id,
                'ip' => $this->ip(),
                'ua' => $this->header('user-agent'),
            ]);

            throw new HttpResponseException(
                back()
                    ->withInput()
                    ->withErrors(['body' => 'Submission tidak valid. Silakan coba lagi.'])
            );
        }

        parent::failedValidation($validator);
    }
}
