<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user');
        $isUpdate = $userId !== null;

        $rules = [
            'name' => 'required|string|max:255',
            'email' => [
                'required', 'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'role' => [
                'nullable', 'string',
                Rule::exists('roles', 'name')->where('guard_name', 'web'),
            ],
        ];

        if ($isUpdate) {
            $rules['password'] = 'nullable|string|min:8';
        } else {
            $rules['password'] = 'required|string|min:8';
        }

        return $rules;
    }
}
