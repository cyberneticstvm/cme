<?php

namespace App\Http\Requests\Admin;

use App\Models\Registration;
use App\Profession;
use App\RegistrationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
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
            'email' => ['required', 'string', 'email:rfc', 'max:254', Rule::unique(Registration::class)->ignore($this->route('registration'))],
            'phone' => ['required', 'string', 'regex:/^\+?[1-9][0-9]{9,14}$/'],
            'organisation' => ['nullable', 'string', 'max:180'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'message' => ['nullable', 'string', 'max:1500'],
            'status' => ['required', Rule::enum(RegistrationStatus::class)],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
