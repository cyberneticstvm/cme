<?php

namespace App\Http\Requests;

use App\Models\Registration;
use App\Profession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }

        if (is_string($this->input('phone'))) {
            $this->merge(['phone' => preg_replace('/[\s().-]+/', '', $this->input('phone'))]);
        }
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:120'],
            'profession' => ['required', Rule::enum(Profession::class)],
            'email' => ['required', 'string', 'email:rfc', 'max:254', Rule::unique(Registration::class)],
            'phone' => ['required', 'string', 'regex:/^\+?[1-9][0-9]{9,14}$/'],
            'organisation' => ['nullable', 'string', 'max:180'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'message' => ['nullable', 'string', 'max:1500'],
            'consent' => ['required', 'accepted'],
            'website' => ['nullable', 'string', 'max:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'profession.enum' => 'Please choose Doctor, Optometrist or Optician.',
            'email.unique' => 'This email is already registered for VISION X 2026. Use the Forgot registration ID option to retrieve your existing ID.',
            'phone.regex' => 'Enter a valid mobile number with 10 to 15 digits, including the country code if applicable.',
            'consent.required' => 'Please agree to the use of your details for your registration and event updates.',
            'consent.accepted' => 'Please agree to the use of your details for your registration and event updates.',
            'website.max' => 'We could not accept this submission. Please leave the website field empty.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['full_name' => 'full name', 'phone' => 'mobile number', 'state' => 'state / region'];
    }
}
