VISION X 2026 | New delegate registration

Hello Organising Team,

A new registration has been successfully saved.

REGISTRATION ID: {!! $registration->reference !!}
Received: {!! $registration->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') !!} IST

Name: {!! $registration->full_name !!}
Profession: {!! $registration->profession->label() !!}
Email: {!! $registration->email !!}
Mobile: {!! $registration->phone !!}
Organisation: {!! $registration->organisation ?: 'Not provided' !!}
Location: {!! $registration->city !!}, {!! $registration->state !!}
@if($registration->message)

Note from the delegate:
{!! $registration->message !!}
@endif

Event communication consent recorded: {!! $registration->consented_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') !!} IST

Reply directly to this email to contact the delegate.

Sunday, 6 December 2026 | Shastamcotta, Kerala
Devi Eye Hospital, in association with Indian Optometrists Association, Kerala.

Internal notification for the VISION X 2026 organising team. Please handle delegate contact details with care.
