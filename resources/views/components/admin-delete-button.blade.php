@props(['registration', 'compact' => false, 'filters' => []])
<button type="button" {{ $attributes->merge(['class' => $compact ? 'admin-delete-row-button' : 'admin-button admin-button-danger-outline']) }}
    data-delete-registration data-delete-url="{{ route('admin.registrations.destroy', array_merge(['registration' => $registration], $filters)) }}"
    data-delegate-name="{{ $registration->full_name }}" data-delegate-email="{{ $registration->email }}" data-delegate-reference="{{ $registration->reference }}"
    aria-label="Delete registration for {{ $registration->full_name }}" aria-haspopup="dialog" aria-controls="admin-delete-dialog" title="Delete registration">
    <x-icon name="trash" /><span>{{ $compact ? 'Delete' : 'Delete registration' }}</span>
</button>
