<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePaymentRequest;
use App\Models\Registration;
use App\PaymentStatus;
use App\Services\RegistrationPassService;
use App\Services\RegistrationWorkflowLock;
use Dompdf\Dompdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class RegistrationPaymentController extends Controller
{
    public function show(Registration $registration): View
    {
        return view('admin.registrations.payment', ['registration' => $registration, 'paymentStatuses' => PaymentStatus::cases(), 'pdfAvailable' => class_exists(Dompdf::class)]);
    }

    public function update(UpdatePaymentRequest $request, Registration $registration, RegistrationWorkflowLock $lock, RegistrationPassService $passes): RedirectResponse
    {
        $lock->run($registration, function (Registration $current) use ($request, $passes): void {
            $status = $request->enum('payment_status', PaymentStatus::class);
            if ($status === PaymentStatus::Confirmed && ($current->payment_status !== PaymentStatus::Confirmed || $current->payment_confirmed_at === null)) {
                $current->payment_confirmed_at = now();
                $current->payment_confirmed_by = config('admin.username');
            }
            if ($status !== PaymentStatus::Confirmed) {
                $passes->revoke($current);
                $current->payment_confirmed_at = null;
                $current->payment_confirmed_by = null;
            }
            $current->payment_status = $status;
            $current->payment_amount = $request->validated('payment_amount');
            $current->payment_reference = $request->validated('payment_reference');
            $current->payment_notes = $request->validated('payment_notes');
            $current->payment_updated_at = now();
            $current->payment_updated_by = config('admin.username');
            $current->save();
        });

        return to_route('admin.registrations.payment', $registration)->with('status', 'Payment details saved. Passes are available only while payment is confirmed and the registration is active.');
    }

    public function sendPass(Registration $registration, RegistrationPassService $passes): RedirectResponse
    {
        try {
            $sent = $passes->generateAndSend($registration);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return to_route('admin.registrations.payment', $registration)->with('warning', 'We could not complete pass delivery. Check SMTP and server settings, then retry. Any successfully generated pass is retained privately.');
        }

        return to_route('admin.registrations.payment', $registration)->with($sent ? 'status' : 'warning', $sent
            ? 'The PDF pass was handed to the mail service for the registered email address.'
            : 'The PDF pass is ready, but email is pending. Configure an outgoing SMTP mailer, then send again.');
    }

    public function download(Registration $registration, RegistrationWorkflowLock $lock, RegistrationPassService $passes): Response
    {
        return $lock->run($registration, function (Registration $current) use ($passes): Response {
            $passes->assertEligible($current);
            abort_unless($current->passPath() && Storage::disk('passes')->exists($current->passPath()), 404);

            return response(Storage::disk('passes')->get($current->passPath()), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="VISION-X-2026-pass-'.$current->reference.'.pdf"',
                'Cache-Control' => 'private, no-store',
            ]);
        });
    }
}
