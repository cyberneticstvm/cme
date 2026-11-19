<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Services\DelegatePassPdf;
use App\Services\PassQrCode;
use App\Services\RegistrationPassService;
use App\Services\RegistrationWorkflowLock;
use Dompdf\Dompdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class RegistrationPassController extends Controller
{
    public function show(Registration $registration): View
    {
        return view('admin.registrations.pass', ['registration' => $registration, 'pdfAvailable' => class_exists(Dompdf::class)]);
    }

    public function sendPass(Registration $registration, RegistrationPassService $passes): RedirectResponse
    {
        try {
            $sent = $passes->generateAndSend($registration);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return to_route('admin.registrations.pass', $registration)->with('warning', 'We could not complete pass delivery. Check SMTP and server settings, then retry. Any successfully generated pass is retained privately.');
        }

        return to_route('admin.registrations.pass', $registration)->with($sent ? 'status' : 'warning', $sent
            ? 'The PDF pass was handed to the mail service for the registered email address.'
            : 'The PDF pass is ready, but email is pending. Configure an outgoing SMTP mailer, then send again.');
    }

    public function download(Registration $registration, RegistrationWorkflowLock $lock, DelegatePassPdf $pdf): Response
    {
        return $lock->run($registration, function (Registration $current) use ($pdf): Response {
            abort_unless($current->canIssuePass(), 403);
            abort_unless($current->passPath() && $current->pass_qr_version === PassQrCode::VERSION
                && $current->pass_issued_at && Storage::disk('passes')->exists($current->passPath()), 404);

            return response($pdf->render($current), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="VISION-X-2026-pass-'.$current->reference.'.pdf"',
                'Cache-Control' => 'private, no-store',
            ]);
        });
    }
}
