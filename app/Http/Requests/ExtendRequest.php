<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExtendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    public function rules(): array
    {
        return [
            'days' => ['sometimes', 'integer', 'min:1', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'days.integer' => 'Extension days must be an integer.',
            'days.min'     => 'Extension must be at least 1 day.',
            'days.max'     => 'Extension cannot exceed 30 days.',
        ];
    }
}
