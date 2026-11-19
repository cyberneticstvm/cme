<?php

namespace App\Http\Controllers\Admin;

use App\Exports\RegistrationExcelExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DestroyRegistrationRequest;
use App\Http\Requests\Admin\RegistrationFilterRequest;
use App\Http\Requests\Admin\UpdateRegistrationRequest;
use App\Models\Registration;
use App\Profession;
use App\RegistrationStatus;
use App\Services\RegistrationCertificateService;
use App\Services\RegistrationMailer;
use App\Services\RegistrationPassService;
use App\Services\RegistrationWorkflowLock;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RegistrationController extends Controller
{
    public function index(RegistrationFilterRequest $request): View
    {
        $filters = array_filter($request->validated(), fn ($value) => $value !== null && $value !== '');
        $totals = Registration::query()->selectRaw('profession, count(*) as aggregate')->groupBy('profession')->pluck('aggregate', 'profession');

        return view('admin.registrations.index', [
            'registrations' => Registration::query()->filtered($filters)->latest('created_at')->latest('id')->paginate(15)->withQueryString(),
            'filters' => $filters,
            'totals' => $totals,
            'total' => $totals->sum(),
            'newCount' => Registration::where('status', RegistrationStatus::New)->count(),
            'professions' => Profession::cases(),
            'statuses' => RegistrationStatus::cases(),
        ]);
    }

    public function show(Registration $registration): View
    {
        return view('admin.registrations.show', ['registration' => $registration, 'professions' => Profession::cases(), 'statuses' => RegistrationStatus::cases()]);
    }

    public function update(UpdateRegistrationRequest $request, Registration $registration, RegistrationWorkflowLock $lock, RegistrationPassService $passes, RegistrationCertificateService $certificates): RedirectResponse
    {
        $lock->run($registration, function (Registration $current) use ($request, $passes, $certificates): void {
            $emailChanged = $current->email !== $request->validated('email');
            $current->fill($request->safe()->only(['full_name', 'profession', 'email', 'phone', 'organisation', 'city', 'state', 'message']));
            $current->status = $request->enum('status', RegistrationStatus::class);
            $current->admin_notes = $request->validated('admin_notes');
            $passChanged = $current->isDirty(['full_name', 'profession', 'email', 'organisation', 'city', 'state'])
                || $current->status === RegistrationStatus::Cancelled;
            $certificateChanged = $current->isDirty(['full_name', 'email', 'profession', 'organisation'])
                || $current->status === RegistrationStatus::Cancelled;
            if ($emailChanged) {
                $current->acknowledgement_sent_at = null;
            }
            try {
                $current->save();
            } catch (UniqueConstraintViolationException $exception) {
                if (! Registration::where('email', $current->email)->whereKeyNot($current->id)->exists()) {
                    throw $exception;
                }
                throw ValidationException::withMessages(['email' => 'This email is already used by another registration.']);
            }
            if ($passChanged && $current->pass_id) {
                $passes->revoke($current);
                $current->save();
            }
            if ($certificateChanged && $current->certificate_id) {
                $certificates->revoke($current);
                $current->save();
            }
        });

        return to_route('admin.registrations.show', $registration)->with('status', 'Registration updated successfully. Existing passes and certificates are revoked when their delegate details change or a registration is cancelled.');
    }

    public function destroy(DestroyRegistrationRequest $request, Registration $registration, RegistrationWorkflowLock $lock, RegistrationPassService $passes, RegistrationCertificateService $certificates): RedirectResponse
    {
        $name = $registration->full_name;
        $lock->run($registration, function (Registration $current) use ($passes, $certificates): void {
            $passes->revoke($current);
            $certificates->revoke($current);
            $current->delete();
        });
        $filters = array_filter($request->safe()->except(['confirm_delete']), fn ($value) => $value !== null && $value !== '');

        return to_route('admin.dashboard', $filters)->with('status', 'Registration for '.$name.' was deleted.');
    }

    public function export(RegistrationFilterRequest $request, RegistrationExcelExport $export): BinaryFileResponse
    {
        $filters = array_filter($request->validated(), fn ($value) => $value !== null && $value !== '');
        $path = $export->create(Registration::query()->filtered($filters), $filters);

        return response()->download($path, 'visionx-registrations-'.now()->format('Y-m-d-His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    public function sendPendingEmails(Registration $registration, RegistrationMailer $mailer): RedirectResponse
    {
        $mailer->sendPending($registration);
        $registration->refresh();
        $complete = $registration->acknowledgement_sent_at !== null && $registration->notification_sent_at !== null;

        return to_route('admin.registrations.show', $registration)->with($complete ? 'status' : 'warning', $complete
            ? 'All registration emails have been handed to the mail service.'
            : 'The record is saved, but some emails are still pending. Check SMTP and receiver settings.');
    }
}
