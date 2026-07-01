<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'book_id' => ['required', 'integer', 'exists:books,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'book_id.required' => 'A book must be selected to make a reservation.',
            'book_id.integer'  => 'The book identifier must be a valid integer.',
            'book_id.exists'   => 'The selected book does not exist.',
        ];
    }
}
