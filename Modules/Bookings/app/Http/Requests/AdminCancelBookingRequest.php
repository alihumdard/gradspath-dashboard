<?php

namespace Modules\Bookings\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminCancelBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
