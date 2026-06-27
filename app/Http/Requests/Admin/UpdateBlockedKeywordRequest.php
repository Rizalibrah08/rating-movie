<?php

namespace App\Http\Requests\Admin;

use App\Models\BlockedKeyword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateBlockedKeywordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('keyword') && ! $this->boolean('is_regex')) {
            $this->merge([
                'keyword' => Str::of((string) $this->input('keyword'))->lower()->trim()->toString(),
            ]);
        }
    }

    public function rules(): array
    {
        $id = $this->route('blocked_keyword')?->id;

        return [
            'keyword' => [
                'required',
                'string',
                'min:2',
                'max:120',
                Rule::unique('blocked_keywords', 'keyword')->ignore($id),
            ],
            'category' => ['required', Rule::in(BlockedKeyword::CATEGORIES)],
            'is_active' => ['nullable', 'boolean'],
            'is_regex' => ['nullable', 'boolean'],
        ];
    }
}
