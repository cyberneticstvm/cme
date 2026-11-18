@use('App\Services\RegistrationPayment')
VISION X 2026 | Your registration ID

Hello {!! $registration->full_name !!},

Your registration ID: {!! $registration->reference !!}

@if (RegistrationPayment::isDue($registration))
If your payment is still outstanding, open the payment page to pay the Rs.{{ RegistrationPayment::FEE }}/- registration fee and see how to request your gate pass.
{!! RegistrationPayment::url($registration->reference) !!}

@endif

Sunday, 6 December 2026
Gemini Heights Auditorium, Sasthamcotta, Kerala
8:45 AM to 4:30 PM IST

If you did not request this reminder, you can safely ignore it.

The VISION X 2026 Organising Team
Devi Eye Hospital
{!! route('home') !!}
