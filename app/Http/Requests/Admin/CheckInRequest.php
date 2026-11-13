<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return ['code' => ['required', 'string', 'max:100']];
    }

    public function messages(): array
    {
        return ['code.required' => 'Scan a pass QR code or paste its check-in code first.', 'code.max' => 'This does not look like a VISION X 2026 pass code.'];
    }
}
