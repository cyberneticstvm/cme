<?php

namespace App\Services;

use App\Models\Registration;
use App\PaymentStatus;
use App\RegistrationStatus;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class RegistrationPayment
{
    public const int FEE = 500;

    public const string QR_IMAGE = 'images/visionx-payment-qr.png';

    public const string SCAN_INSTRUCTIONS = 'Greetings from DEVI EYE HOSPITAL. Please scan the QR with cards and UPI to complete your payment.';

    public static function isDue(Registration $registration): bool
    {
        return $registration->status !== RegistrationStatus::Cancelled
            && ($registration->payment_status ?? PaymentStatus::Pending) === PaymentStatus::Pending;
    }

    public static function url(string $reference): string
    {
        return URL::signedRoute('registrations.payment', ['reference' => $reference]);
    }

    /** @return array{upi: string, googlePay: string} */
    public static function appLinks(string $reference): array
    {
        $payment = http_build_query([
            'pa' => 'vyapar.175693565052@hdfcbank',
            'pn' => 'DEVI EYE HOSPITAL',
            'mc' => '8062',
            'tr' => (string) Str::ulid(),
            'tn' => $reference,
            'am' => number_format(self::FEE, 2, '.', ''),
            'cu' => 'INR',
        ], '', '&', PHP_QUERY_RFC3986);

        return [
            'upi' => 'upi://pay?'.$payment,
            'googlePay' => 'gpay://upi/pay?'.$payment,
        ];
    }

    public static function whatsAppNumber(): ?string
    {
        $number = preg_replace('/[\s()+.\-]/', '', (string) config('services.whatsapp.number'));

        return preg_match('/^[1-9][0-9]{6,14}$/D', $number) ? $number : null;
    }

    public static function whatsAppUrl(string $reference): ?string
    {
        $number = self::whatsAppNumber();

        return $number ? 'https://wa.me/'.$number.'?text='.rawurlencode(
            'Hello, I am sharing my VISION X 2026 payment confirmation. Registration ID: '.$reference,
        ) : null;
    }
}
