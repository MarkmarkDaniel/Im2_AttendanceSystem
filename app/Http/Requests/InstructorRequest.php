<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class InstructorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $instructor   = $this->route('instructor');
        $instructorId = $instructor?->id;
        $userId       = $instructor?->user_id;

        $passwordRule = $this->isMethod('POST') ? 'required' : 'nullable';

        return [
            'first_name'  => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name'   => ['required', 'string', 'max:100'],

            'employee_number' => [
                'required', 'string', 'max:30',
                Rule::unique('instructors', 'employee_number')->ignore($instructorId),
            ],

            'email' => [
                'required', 'string', 'email', 'max:150',
                Rule::unique('instructors', 'email')->ignore($instructorId),
                Rule::unique('users', 'email')->ignore($userId),
            ],

            'department_id' => ['required', 'integer', 'exists:departments,id'],

            'status' => ['required', Rule::in(['Active', 'Inactive'])],

            'password' => [
                $passwordRule,
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],

            // Only offered on the edit form
            'profile_image'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_profile_image' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'profile_image.max'      => 'The profile photo may not be larger than 2 MB.',
            'employee_number.unique' => 'That employee number is already in use.',
            'email.unique'           => 'That email address is already in use.',
        ];
    }

    public function attributes(): array
    {
        return ['department_id' => 'department'];
    }
}
