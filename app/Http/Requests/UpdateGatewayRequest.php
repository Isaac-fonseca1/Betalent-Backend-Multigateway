<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateGatewayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('gateway'));
    }

    public function rules(): array
    {
        $gatewayId = $this->route('gateway')->id;
        return [
            'name' => 'sometimes|required|string|max:255',
            'driver' => 'sometimes|required|string|unique:gateways,driver,' . $gatewayId,
            'priority' => 'integer|min:1',
            'is_active' => 'boolean'
        ];
    }
}
