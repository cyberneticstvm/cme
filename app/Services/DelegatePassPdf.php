<?php

namespace App\Services;

use App\Models\Registration;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Validation\ValidationException;

class DelegatePassPdf
{
    public function __construct(private PassQrCode $qr) {}

    public function render(Registration $registration): string
    {
        if (! $registration->canIssuePass()) {
            throw ValidationException::withMessages(['payment_status' => 'Payment must be confirmed before a pass can be generated.']);
        }
        if (! class_exists(Dompdf::class)) {
            throw ValidationException::withMessages(['registration' => 'The PDF service is not configured yet. Please contact your system administrator.']);
        }
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('isJavascriptEnabled', false);
        $options->set('chroot', public_path('images'));
        $options->set('defaultFont', 'DejaVu Sans');
        $pdf = new Dompdf($options);
        $pdf->setPaper('A5', 'landscape');
        $pdf->loadHtml(view('passes.delegate', ['registration' => $registration, 'qrDataUri' => $this->qr->dataUri($registration->pass_id)])->render(), 'UTF-8');
        $pdf->render();

        return $pdf->output();
    }
}
