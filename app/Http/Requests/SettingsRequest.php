<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_rate' => ['required', 'numeric', 'min:0'],
            'free_shipping_threshold' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'stripe_key' => ['nullable', 'string'],
            'stripe_secret' => ['nullable', 'string'],
            'stripe_webhook_secret' => ['nullable', 'string'],
            'paypal_client_id' => ['nullable', 'string'],
            'paypal_secret' => ['nullable', 'string'],
            'paypal_mode' => ['nullable', 'in:sandbox,live'],
        ];
    }
}
