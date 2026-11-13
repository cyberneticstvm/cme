<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:12px;line-height:1.7;border-top:1px solid #e1eaf1">
    @foreach(['Name' => $registration->full_name, 'Profession' => $registration->profession->label(), 'Email' => $registration->email, 'Mobile' => $registration->phone, 'Organisation' => $registration->organisation ?: 'Not provided', 'Location' => $registration->city.', '.$registration->state] as $label => $value)
        <tr><td class="mail-detail-label" valign="top" width="30%" style="width:30%;padding:12px 12px 12px 0;color:#7790a3;border-bottom:1px solid #e1eaf1">{{ $label }}</td><td valign="top" style="padding:12px 0;color:#284e6c;font-weight:{{ $loop->first ? 'bold' : 'normal' }};border-bottom:1px solid #e1eaf1;overflow-wrap:anywhere">{{ $value }}</td></tr>
    @endforeach
</table>
