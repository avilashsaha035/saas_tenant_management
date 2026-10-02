<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'company_slug' => ['nullable', 'string', 'alpha_dash', 'max:100', 'unique:tenants,slug'],
            'domain'       => ['nullable', 'string', 'max:255', 'unique:tenants,domain'],
            'owner_name'   => ['required', 'string', 'max:255'],
            'email'        => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
            'plan_slug'    => ['nullable', 'string', 'exists:plans,slug'],
            'settings'     => ['nullable', 'array'],
        ];
    }
}
