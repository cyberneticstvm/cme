<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrationEmailRequest;
use App\Mail\RegistrationIdReminder;
use App\Models\Registration;
use App\Services\RegistrationWorkflowLock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class RegistrationRecoveryController extends Controller
{
    public function check(RegistrationEmailRequest $request): JsonResponse
    {
        return response()->json(['registered' => Registration::where('email', $request->validated('email'))->exists()])
            ->header('Cache-Control', 'private, no-store');
    }

    public function create(): Response
    {
        return response()->view('registrations.recover')->header('Cache-Control', 'private, no-store');
    }

    public function send(RegistrationEmailRequest $request, RegistrationWorkflowLock $lock): RedirectResponse
    {
        $registration = Registration::where('email', $request->validated('email'))->first();
        if ($registration) {
            try {
                if (in_array(config('mail.default'), ['log', 'array'], true)) {
                    throw ValidationException::withMessages(['email' => 'Email delivery is temporarily unavailable. Please contact the organising team or try again later.']);
                }
                $lock->run($registration, function (Registration $current) use ($request): void {
                    if ($current->email === $request->validated('email')) {
                        Mail::to($current->email, $current->full_name)->send(new RegistrationIdReminder($current));
                    }
                });
            } catch (ValidationException $exception) {
                throw $exception;
            } catch (Throwable $exception) {
                report($exception);
                throw ValidationException::withMessages(['email' => 'We could not send the email right now. Please try again later or contact the organising team.']);
            }
        }

        return to_route('registrations.recover')->with('recovery_status', 'If this email is registered with us, we have sent its registration ID. Please check your inbox and spam folder.');
    }
}
