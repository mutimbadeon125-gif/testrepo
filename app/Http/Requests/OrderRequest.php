<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'delivery_address' => 'required|string|min:10',
            'phone_number' => 'required|string',
            'delivery_date' => 'nullable|date',
            'special_instructions' => 'nullable|string',
            'payment_method' => 'required|in:ecocash,onemoney,zipit,paynow',  
        ];
    }
}
