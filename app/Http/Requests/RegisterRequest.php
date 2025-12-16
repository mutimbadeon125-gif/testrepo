<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|unique:users,phone',
            'location' => 'required|string|max:255',
            'role' => 'required|in:customer,vendor',

            //vendor-only fields
            'business_name' => 'required_if:role,vendor|string|max:255',
            'business_category' => 'required_if:role,vendor|in:produce,dairy,craft,bakery,other'
        ];
    }
}
