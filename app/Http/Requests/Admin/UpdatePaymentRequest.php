<?php

namespace App\Http\Requests\Admin;

use App\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'payment_status' => ['required', Rule::enum(PaymentStatus::class)],
            'payment_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'payment_notes' => ['nullable', 'string', 'max:3000'],
            'confirm_payment' => ['required_if:payment_status,confirmed', 'accepted_if:payment_status,confirmed'],
        ];
    }

    public function messages(): array
    {
        return ['confirm_payment.required_if' => 'Please confirm that you have verified receipt of this payment.', 'confirm_payment.accepted_if' => 'Please confirm that you have verified receipt of this payment.'];
    }
}
