@use('App\Services\RegistrationPayment')
VISION X 2026 | Registration received

Hello {!! $registration->full_name !!},

Thank you for registering for VISION X 2026. Your registration has been received.

YOUR REGISTRATION ID: {!! $registration->reference !!}

@if ($paymentUrl)
Registration fee: Rs.{{ RegistrationPayment::FEE }}/-

{{ RegistrationPayment::SCAN_INSTRUCTIONS }}
Open the payment page to scan the QR or launch a supported UPI app on mobile with Rs.{{ RegistrationPayment::FEE }} and registration ID {!! $registration->reference !!} as the payment note.
{!! $paymentUrl !!}

After paying, please send your payment screenshot and registration ID {!! $registration->reference !!} to the organising team. They will verify your payment and issue your gate pass.
@if ($whatsAppUrl)
WhatsApp: +{!! $whatsAppNumber !!}
{!! $whatsAppUrl !!}
@else
Event website: {!! route('home') !!}#faq
@endif

@endif
Name: {!! $registration->full_name !!}
Profession: {!! $registration->profession->label() !!}
Email: {!! $registration->email !!}
Mobile: {!! $registration->phone !!}
Organisation: {!! $registration->organisation ?: 'Not provided' !!}
Location: {!! $registration->city !!}, {!! $registration->state !!}

Sunday, 6 December 2026
Gemini Heights Auditorium, Sasthamcotta, Kerala
8:45 AM to 4:30 PM IST

Program Schedule: {!! route('program-schedule') !!}

Warm regards,
The VISION X 2026 Organising Team
Devi Eye Hospital
In association with Indian Optometrists Association, Kerala

You received this email because a registration was submitted using this email address. If you did not register, contact the organising team through the website.
