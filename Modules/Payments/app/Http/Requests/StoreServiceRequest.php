<?php

namespace Modules\Payments\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_name' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:300'],
            'is_active' => ['nullable', 'boolean'],
            'available_session_types' => ['nullable', 'array'],
            'available_session_types.*' => ['in:1on1,1on3,1on5'],
            'price_1on1' => ['nullable', 'numeric', 'min:0'],
            'price_1on3_per_person' => ['nullable', 'numeric', 'min:0'],
            'price_1on5_per_person' => ['nullable', 'numeric', 'min:0'],
            'is_office_hours' => ['nullable', 'boolean'],
            'office_hours_subscription_price' => ['nullable', 'numeric', 'min:0'],
            'credit_cost_1on1' => ['nullable', 'integer', 'min:0'],
            'credit_cost_1on3' => ['nullable', 'integer', 'min:0'],
            'credit_cost_1on5' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'notes' => ['required', 'string', 'max:1000'],
            'manual_section' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $sessionTypes = collect($this->input('available_session_types', []));
            $hasOfficeHours = $this->boolean('is_office_hours');

            if ($sessionTypes->isEmpty() && ! $hasOfficeHours) {
                $validator->errors()->add('available_session_types', 'Select at least one session type or enable Office Hours.');
            }

            foreach (
                [
                    '1on1' => ['price' => 'price_1on1', 'credit' => 'credit_cost_1on1'],
                    '1on3' => ['price' => 'price_1on3_per_person', 'credit' => 'credit_cost_1on3'],
                    '1on5' => ['price' => 'price_1on5_per_person', 'credit' => 'credit_cost_1on5'],
                ] as $sessionType => $fields
            ) {
                if (! $sessionTypes->contains($sessionType)) {
                    continue;
                }

                if ($this->input($fields['price']) === null || $this->input($fields['price']) === '') {
                    $validator->errors()->add($fields['price'], "A price is required when {$sessionType} is enabled.");
                }

                if ($this->input($fields['credit']) === null || $this->input($fields['credit']) === '') {
                    $validator->errors()->add($fields['credit'], "A credit cost is required when {$sessionType} is enabled.");
                }
            }

            if ($hasOfficeHours && ($this->input('office_hours_subscription_price') === null || $this->input('office_hours_subscription_price') === '')) {
                $validator->errors()->add('office_hours_subscription_price', 'Office Hours subscription price is required when the Office Hours flag is enabled.');
            }
        });
    }
}
