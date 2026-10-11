<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Identity check. These three are matched against the student
            // masterlist in AuthController::register(), not here, so that a
            // wrong guess never reveals which part was wrong.
            'student_number' => ['required', 'string', 'max:30'],
            'last_name'      => ['required', 'string', 'max:100'],
            'birth_date'     => ['required', 'date', 'before:today'],

            'email' => [
                'required', 'string', 'email', 'max:150',
                'unique:users,email',
                'unique:students,email',
            ],

            'phone' => ['nullable', 'string', 'max:30'],

            'password' => [
                'required',
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'       => 'That email address is already registered.',
            'birth_date.before'  => 'Please enter your date of birth.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }
}
