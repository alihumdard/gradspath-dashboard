<?php

namespace Modules\Payments\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseCreditsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'credits' => ['required', 'integer', 'min:1', 'max:1000'],
            'stripe_payment_id' => ['nullable', 'string', 'max:255'],
            'office_hours_program' => ['nullable', 'in:mba,law,therapy'],
        ];
    }
}
