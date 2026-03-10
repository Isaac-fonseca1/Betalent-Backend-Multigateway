<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('client'));
    }

    public function rules(): array
    {
        $clientId = $this->route('client')->id;
        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:clients,email,' . $clientId,
            'document' => 'sometimes|required|string|unique:clients,document,' . $clientId,
            'document_type' => 'sometimes|required|in:CPF,CNPJ',
            'phone' => 'nullable|string'
        ];
    }
}
