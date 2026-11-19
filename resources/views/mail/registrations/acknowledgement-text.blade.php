VISION X 2026 | Registration received

Hello {!! $registration->full_name !!},

Thank you for registering for VISION X 2026. Your registration has been received.

YOUR REGISTRATION ID: {!! $registration->reference !!}

Registration is free.@if ($registration->canIssuePass()) The organising team will email your delegate pass separately.@endif

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
