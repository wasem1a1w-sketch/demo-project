<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CouponCodeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => 'required|string',
        ];
    }
}
