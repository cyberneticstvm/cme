<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Services\RegistrationCertificateService;
use App\Services\RegistrationWorkflowLock;
use Dompdf\Dompdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class RegistrationCertificateController extends Controller
{
    public function show(Registration $registration): View
    {
        return view('admin.registrations.certificate', ['registration' => $registration, 'pdfAvailable' => class_exists(Dompdf::class)]);
    }

    public function store(Registration $registration, RegistrationCertificateService $certificates): RedirectResponse
    {
        try {
            $sent = $certificates->generateAndSend($registration, (string) config('admin.username'));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return to_route('admin.registrations.certificate', $registration)->with('warning', 'Certificate delivery could not be completed. Check SMTP and server settings, then retry. Any successfully generated certificate is retained privately.');
        }

        return to_route('admin.registrations.certificate', $registration)->with($sent ? 'status' : 'warning', $sent
            ? 'The participation certificate PDF was handed to the mail service for the registered email address.'
            : 'The certificate PDF is ready, but email delivery is pending. Configure an outgoing SMTP mailer, then send again.');
    }

    public function download(Registration $registration, RegistrationWorkflowLock $lock, RegistrationCertificateService $certificates): Response
    {
        return $lock->run($registration, function (Registration $current) use ($certificates): Response {
            $certificates->assertEligible($current);
            abort_unless($current->certificatePath() && Storage::disk('certificates')->exists($current->certificatePath()), 404);

            return response(Storage::disk('certificates')->get($current->certificatePath()), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="VISION-X-2026-certificate-'.$current->reference.'.pdf"',
                'Cache-Control' => 'private, no-store',
            ]);
        });
    }
}
