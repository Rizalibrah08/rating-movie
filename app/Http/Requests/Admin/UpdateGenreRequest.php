<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGenreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $genreId = $this->route('genre')?->id;

        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:60',
                Rule::unique('genres', 'name')->ignore($genreId),
            ],
        ];
    }
}
