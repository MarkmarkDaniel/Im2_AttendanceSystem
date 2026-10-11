<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $classId = $this->route('class')?->id;

        return [
            'subject_id' => [
                'required', 'integer', 'exists:subjects,id',
                // One offering per subject + section + school year + semester
                Rule::unique('classes', 'subject_id')
                    ->where('section', $this->input('section'))
                    ->where('school_year', $this->input('school_year'))
                    ->where('semester', $this->input('semester'))
                    ->ignore($classId),
            ],

            'instructor_id' => [
                'required', 'integer',
                // The subject must be assigned to the instructor first
                Rule::exists('instructor_subject', 'instructor_id')
                    ->where('subject_id', $this->input('subject_id')),
            ],

            'school_year' => ['required', 'string', 'max:20'],

            'semester' => [
                'required',
                Rule::in(['1st Semester', '2nd Semester', 'Summer']),
            ],

            'section' => ['required', 'string', 'max:20'],
            'room'    => ['nullable', 'string', 'max:50'],

            'schedule_day' => [
                'required',
                Rule::in(['Monday', 'Tuesday', 'Wednesday', 'Thursday',
                          'Friday', 'Saturday', 'Sunday']),
            ],

            'start_time' => ['required', 'date_format:H:i,H:i:s'],
            'end_time'   => ['required', 'date_format:H:i,H:i:s', 'after:start_time'],

            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'subject_id.unique'    => 'That subject already has a class for this section, school year, and semester.',
            'instructor_id.exists' => 'That instructor is not assigned to the selected subject. Assign it from the Instructors page first.',
            'end_time.after'       => 'The end time must be later than the start time.',
        ];
    }

    public function attributes(): array
    {
        return [
            'subject_id'    => 'subject',
            'instructor_id' => 'instructor',
        ];
    }
}
