<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'vendor';
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
            'description' => 'required|string',
            'category' => 'required|string|max:255',
            'price' => 'required|numeric|min:0.01',
            'original_price' => 'nullable|numeric|min:0.01',
            'stock' => 'required|integer|min:0',
            'sku' => 'required|string|max:255|unique:products,sku' . $this->id,
            'weight' => 'nullable|string',
            'origin' => 'nullable|string',
            'harvest_date' => 'nullable|date',
            'image_url' => 'nullable|string'
        ];
    }
}
