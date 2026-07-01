<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // `login` accepts either an email address or a phone number.
            'login'    => ['required', 'string', 'max:160'],
            'password' => ['required', 'string'],
        ];
    }
}
