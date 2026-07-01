<?php

namespace App\Http\Requests;

use App\Enums\CopyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookCopyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in(array_column(CopyStatus::cases(), 'value')),
            ],
        ];
    }

    public function messages(): array
    {
        $allowed = implode(', ', array_column(CopyStatus::cases(), 'value'));

        return [
            'status.required' => 'The status field is required.',
            'status.in'       => "The status must be one of: {$allowed}.",
        ];
    }
}
