<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>VISION X 2026 Delegate Pass</title>
    <style>
        @page {
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "DejaVu Sans", sans-serif;
            color: #123657;
            font-size: 10px;
            background: #edf5fa;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .page {
            padding: 18px 24px;
        }

        .card {
            background: #fff;
            border: 1px solid #d8e6f0;
        }

        .top {
            padding: 8px 24px;
            border-top: 6px solid #fa7c18;
        }

        .brand {
            width: 130px;
            height: 44px;
            object-fit: contain;
        }

        .hospital {
            width: 90px;
            height: 44px;
            object-fit: contain;
        }

        .eyebrow {
            color: #6a899e;
            font-size: 7px;
            letter-spacing: 1.7px;
        }

        .banner {
            padding: 10px 24px;
            background: #092d62;
            color: #fff;
        }

        .banner h1 {
            margin: 3px 0;
            font-size: 20px;
            line-height: 1.2;
            letter-spacing: -.5px;
        }

        .banner p {
            margin: 0;
            line-height: 1.5;
            color: #bfdbef;
            font-size: 9px;
        }

        .paid {
            background: #e8f7ee;
            color: #246747;
            display: inline-block;
            white-space: nowrap;
            padding: 8px 10px;
            font-size: 8px;
            line-height: 1.5;
            font-weight: bold;
        }

        .body {
            padding: 19px 24px 14px;
        }

        .name {
            font-size: 23px;
            color: #092d62;
            font-weight: bold;
            line-height: 1.35;
            margin: 8px 0 5px;
            overflow-wrap: anywhere;
        }

        .name-long {
            font-size: 14px;
        }

        .profession {
            color: #e77013;
            font-weight: bold;
            font-size: 11px;
        }

        .organisation {
            font-size: 9px;
            color: #678599;
            margin: 5px 0 0;
        }

        .id-cell {
            width: 145px;
            background: #fff;
            padding: 10px;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #d6e7f4;
        }

        .id {
            font-size: 23px;
            line-height: 1.2;
            font-weight: bold;
            color: #092d62;
            letter-spacing: 3px;
            margin: 4px 0 0;
        }

        .pass-qr {
            width: 108px;
            height: 108px;
            display: block;
            margin: 5px auto 0;
        }

        .qr-caption {
            font-size: 6px;
            letter-spacing: 1px;
            color: #6a899e;
            margin-top: 3px;
        }

        .event {
            border-top: 1px solid #e2edf5;
            margin-top: 15px;
            padding-top: 12px;
        }

        .event strong {
            font-size: 11px;
            color: #092d62;
        }

        .event p {
            font-size: 8px;
            color: #7891a4;
            margin: 5px 0;
        }

        .foot {
            background: #f5f9fc;
            padding: 10px 24px;
            border-top: 1px dashed #c8dce9;
        }

        .foot p {
            font-size: 7px;
            color: #6b8799;
            margin: 4px 0;
        }

        .serial {
            font-family: monospace;
            font-size: 6px;
            color: #8aa0af;
        }

        .page-note {
            font-size: 7px;
            color: #6f899c;
            text-align: center;
            margin: 10px 0 0;
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="card">
            <div class="top">
                <table>
                    <tr>
                        <td><img class="brand" src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/visionx-2026-logo.png'))) }}" alt="VISION X 2026"></td>
                        <td style="text-align:right"><img class="hospital" src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/devi-eye-hospital-transparent.png'))) }}" alt="Devi Eye Hospital"></td>
                    </tr>
                </table>
            </div>
            <div class="banner">
                <table>
                    <tr>
                        <td>
                            <p class="eyebrow" style="color:#a9cbe3">CONTINUING MEDICAL EDUCATION</p>
                            <h1>Your delegate pass.</h1>
                            <p>A shared vision. A day of new perspectives.</p>
                        </td>
                        <td style="text-align:right;width:170px"><span class="paid">PAYMENT CONFIRMED</span></td>
                    </tr>
                </table>
            </div>
            <div class="body">
                <table>
                    <tr>
                        <td style="padding-right:20px;vertical-align:top">
                            <div class="eyebrow">WELCOME TO VISION X 2026</div>
                            <div class="name{{ mb_strlen($registration->full_name) > 55 ? '-long' : '' }}">{{ $registration->full_name }}</div>
                            <div class="profession">{{ $registration->profession->label() }}</div>@if($registration->organisation)<p class="organisation">{{ Illuminate\Support\Str::limit($registration->organisation, 70) }}</p>@endif<div class="event">
                                <table>
                                    <tr>
                                        <td style="width:52%">
                                            <div class="eyebrow" style="margin-bottom:6px">THE DATE</div><strong>Sunday, 6 December 2026</strong>
                                            <p>Show this pass at the entry desk.</p>
                                        </td>
                                        <td>
                                            <div class="eyebrow" style="margin-bottom:6px">THE DESTINATION</div><strong>Sasthamcotta, Kerala</strong>
                                            <p>Exact venue and timings will follow.</p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                        <td class="id-cell">
                            <div class="eyebrow">REGISTRATION ID</div>@if(strlen($registration->reference) > 5)<div class="id" style="font-size:8px;letter-spacing:.4px;line-height:1.6">@foreach(str_split($registration->reference, 16) as $part)<span style="display:block">{{ $part }}</span>@endforeach</div>@else<div class="id">{{ $registration->reference }}</div>@endif<img class="pass-qr" src="{{ $qrDataUri }}" alt="Personal delegate check-in QR code">
                            <div class="qr-caption">SCAN ONCE AT ENTRY</div>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="foot">
                <table>
                    <tr>
                        <td>
                            <p><strong>Devi Eye Hospital</strong> &middot; In association with IOA Kerala</p>
                            <p>Issued {{ $registration->pass_issued_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST &middot; For the named delegate only.</p>
                        </td>
                        <td style="text-align:right;width:225px"><span class="serial">PASS {{ $registration->pass_id }}</span></td>
                    </tr>
                </table>
            </div>
        </div>
        <p class="page-note">Single entry for the named delegate. Our team will scan this QR code and verify payment at check-in. Copies share the same single-use pass.</p>
    </div>
</body>

</html>