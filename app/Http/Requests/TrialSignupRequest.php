<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrialSignupRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => ['required', 'string', 'regex:/^\d{10}$/'],
            'business_name' => 'required|string|max:120',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid 10-digit US phone number (e.g. 3478245640).',
            'email.unique' => 'An account with this email already exists. Try logging in instead.',
        ];
    }
}
