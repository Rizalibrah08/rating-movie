<?php

namespace App\Http\Requests;

use App\Models\ReviewReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReviewReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::in(ReviewReport::REASONS)],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
