<?php

namespace App\Http\Requests\Admin;

use App\Profession;
use App\RegistrationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrationFilterRequest extends FormRequest
{
    protected $redirectRoute = 'admin.dashboard';

    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'profession' => ['nullable', Rule::enum(Profession::class)],
            'status' => ['nullable', Rule::enum(RegistrationStatus::class)],
            'attendance' => ['nullable', Rule::in(['checked_in', 'not_checked_in'])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', Rule::when($this->filled('from'), ['after_or_equal:from'])],
        ];
    }
}
