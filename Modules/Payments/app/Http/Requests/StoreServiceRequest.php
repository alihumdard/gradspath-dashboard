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
            'platform_fee_1on1' => ['nullable', 'numeric', 'min:0'],
            'mentor_payout_1on1' => ['nullable', 'numeric', 'min:0'],
            'price_1on3_per_person' => ['nullable', 'numeric', 'min:0'],
            'price_1on3_total' => ['nullable', 'numeric', 'min:0'],
            'platform_fee_1on3' => ['nullable', 'numeric', 'min:0'],
            'mentor_payout_1on3' => ['nullable', 'numeric', 'min:0'],
            'price_1on5_per_person' => ['nullable', 'numeric', 'min:0'],
            'price_1on5_total' => ['nullable', 'numeric', 'min:0'],
            'platform_fee_1on5' => ['nullable', 'numeric', 'min:0'],
            'mentor_payout_1on5' => ['nullable', 'numeric', 'min:0'],
            'is_office_hours' => ['nullable', 'boolean'],
            'office_hours_subscription_price' => ['nullable', 'numeric', 'min:0'],
            'office_hours_mentor_payout_per_attendee' => ['nullable', 'numeric', 'min:0'],
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
                    '1on1' => [
                        'price' => 'price_1on1',
                        'platform_fee' => 'platform_fee_1on1',
                        'mentor_payout' => 'mentor_payout_1on1',
                    ],
                    '1on3' => [
                        'price' => 'price_1on3_total',
                        'platform_fee' => 'platform_fee_1on3',
                        'mentor_payout' => 'mentor_payout_1on3',
                    ],
                    '1on5' => [
                        'price' => 'price_1on5_total',
                        'platform_fee' => 'platform_fee_1on5',
                        'mentor_payout' => 'mentor_payout_1on5',
                    ],
                ] as $sessionType => $fields
            ) {
                if (! $sessionTypes->contains($sessionType)) {
                    continue;
                }

                if ($this->input($fields['price']) === null || $this->input($fields['price']) === '') {
                    $validator->errors()->add($fields['price'], "A price is required when {$sessionType} is enabled.");
                }

                if ($this->input($fields['platform_fee']) === null || $this->input($fields['platform_fee']) === '') {
                    $validator->errors()->add($fields['platform_fee'], "An admin split amount is required when {$sessionType} is enabled.");
                }

                if ($this->input($fields['mentor_payout']) === null || $this->input($fields['mentor_payout']) === '') {
                    $validator->errors()->add($fields['mentor_payout'], "A mentor split amount is required when {$sessionType} is enabled.");
                }

                $this->validateSplitTotal($validator, $fields['price'], $fields['platform_fee'], $fields['mentor_payout']);
            }

            if ($hasOfficeHours && ($this->input('office_hours_subscription_price') === null || $this->input('office_hours_subscription_price') === '')) {
                $validator->errors()->add('office_hours_subscription_price', 'Office Hours subscription price is required when the Office Hours flag is enabled.');
            }

            if ($hasOfficeHours && ($this->input('office_hours_mentor_payout_per_attendee') === null || $this->input('office_hours_mentor_payout_per_attendee') === '')) {
                $validator->errors()->add('office_hours_mentor_payout_per_attendee', 'Office Hours mentor payout per attendee is required when the Office Hours flag is enabled.');
            }
        });
    }

    private function validateSplitTotal(Validator $validator, string $priceField, string $platformField, string $mentorField): void
    {
        if (
            $this->input($priceField) === null || $this->input($priceField) === ''
            || $this->input($platformField) === null || $this->input($platformField) === ''
            || $this->input($mentorField) === null || $this->input($mentorField) === ''
        ) {
            return;
        }

        $priceCents = (int) round((float) $this->input($priceField) * 100);
        $splitCents = (int) round(((float) $this->input($platformField) + (float) $this->input($mentorField)) * 100);

        if ($priceCents !== $splitCents) {
            $validator->errors()->add($platformField, 'Admin and mentor split amounts must add up to the student price.');
            $validator->errors()->add($mentorField, 'Admin and mentor split amounts must add up to the student price.');
        }
    }
}
