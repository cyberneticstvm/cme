<?php

namespace App\Services;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use InvalidArgumentException;

class PassQrCode
{
    public const int VERSION = 1;

    public const string PREFIX = 'VISIONX2026:1:';

    public function token(string $code): ?string
    {
        $value = trim($code);
        if (str_starts_with(strtoupper($value), self::PREFIX)) {
            $value = substr($value, strlen(self::PREFIX));
        }

        return preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i', $value)
            ? strtolower($value) : null;
    }

    public function payload(string $passId): string
    {
        $token = $this->token($passId);
        if ($token === null) {
            throw new InvalidArgumentException('A valid pass identifier is required.');
        }

        return self::PREFIX.$token;
    }

    public function dataUri(string $passId): string
    {
        $code = new QrCode(data: $this->payload($passId), encoding: new Encoding('ISO-8859-1'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium, size: 300, margin: 24);

        return (new PngWriter)->write($code)->getDataUri();
    }
}
