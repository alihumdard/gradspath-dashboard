<?php

namespace Modules\MentorNotes\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMentorNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'worked_on' => ['required', 'string', 'max:10000'],
            'next_steps' => ['required', 'string', 'max:10000'],
            'session_result' => ['required', 'string', 'max:10000'],
            'strengths_challenges' => ['required', 'string', 'max:10000'],
            'other_notes' => ['required', 'string', 'max:10000'],
        ];
    }
}
