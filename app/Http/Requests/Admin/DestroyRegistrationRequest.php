<?php

namespace App\Http\Requests\Admin;

class DestroyRegistrationRequest extends RegistrationFilterRequest
{
    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [...parent::rules(), 'confirm_delete' => ['required', 'accepted']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'confirm_delete.required' => 'Please confirm the registration deletion.',
            'confirm_delete.accepted' => 'Please confirm the registration deletion.',
        ];
    }
}
