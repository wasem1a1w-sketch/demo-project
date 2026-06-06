<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class OrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(OrderStatus::class)],
            'payment_status' => ['required', new Enum(PaymentStatus::class)],
        ];
    }
}
