<?php

namespace App\Http\Requests\Auth;

use App\Domain\User\ValueObjects\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'type' => ['required', 'string', Rule::in(UserType::getValidTypes())],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email is required',
            'email.email' => 'Email must be a valid email address',
            'password.required' => 'Password is required',
            'type.required' => 'User type is required',
            'type.in' => 'User type must be one of: ' . implode(', ', UserType::getValidTypes()),
        ];
    }
}
