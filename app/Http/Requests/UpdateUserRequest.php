<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['sometimes', 'required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'role'     => ['nullable', 'in:admin,manager,member'],
            'status'   => ['nullable', 'in:active,inactive'],
        ];
    }
}
