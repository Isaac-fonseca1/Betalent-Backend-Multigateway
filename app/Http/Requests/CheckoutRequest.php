<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public route as per README
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'credit_card' => preg_replace('/[^0-9]/', '', $this->credit_card),
            'cvv' => preg_replace('/[^0-9]/', '', $this->cvv),
        ]);
    }

    public function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'credit_card' => 'required|string|size:16',
            'cvv' => 'required|string|size:3',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ];
    }
}
