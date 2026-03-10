<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Gateway;

class StoreGatewayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', Gateway::class);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'driver' => 'required|string|unique:gateways,driver',
            'priority' => 'integer|min:1',
            'is_active' => 'boolean'
        ];
    }
}
