<?php

namespace App\Services;

use App\Models\Registration;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Validation\ValidationException;

class ParticipationCertificatePdf
{
    public function render(Registration $registration): string
    {
        if (! $registration->canIssueCertificate()) {
            throw ValidationException::withMessages(['certificate' => 'Recorded attendance is required before a participation certificate can be generated.']);
        }
        if (! class_exists(Dompdf::class)) {
            throw ValidationException::withMessages(['certificate' => 'The PDF service is not configured yet. Please contact your system administrator.']);
        }
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('isJavascriptEnabled', false);
        $options->set('chroot', public_path('images'));
        $options->set('defaultFont', 'DejaVu Sans');
        $pdf = new Dompdf($options);
        $pdf->setPaper('A4', 'landscape');
        $pdf->loadHtml(view('certificates.participation', ['registration' => $registration])->render(), 'UTF-8');
        $pdf->render();

        return $pdf->output();
    }
}
