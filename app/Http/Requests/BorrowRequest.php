<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BorrowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    public function rules(): array
    {
        return [
            'copy_id' => ['required', 'integer', 'min:1', 'exists:book_copies,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'copy_id.required' => 'A book copy must be selected.',
            'copy_id.integer'  => 'The copy ID must be an integer.',
            'copy_id.exists'   => 'The selected book copy does not exist.',
        ];
    }
}
