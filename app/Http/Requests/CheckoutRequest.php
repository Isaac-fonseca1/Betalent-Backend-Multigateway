<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public route as per README
    }

    public function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'credit_card' => 'required|string|min:13|max:19',
            'cvv' => 'required|string|min:3|max:4',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ];
    }
}
