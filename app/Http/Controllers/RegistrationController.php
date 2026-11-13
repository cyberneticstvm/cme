<?php

namespace App\Http\Controllers;

use App\Exceptions\RegistrationCapacityReached;
use App\Http\Requests\StoreRegistrationRequest;
use App\Models\Registration;
use App\Profession;
use App\Services\RegistrationMailer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(): View
    {
        return view('registrations.create', ['professions' => Profession::cases()]);
    }

    public function store(StoreRegistrationRequest $request, RegistrationMailer $mailer): RedirectResponse
    {
        $registration = new Registration($request->safe()->only([
            'full_name', 'profession', 'email', 'phone', 'organisation', 'city', 'state', 'message',
        ]));
        $registration->consented_at = now();

        try {
            $registration->save();
        } catch (RegistrationCapacityReached) {
            throw ValidationException::withMessages(['registration' => 'Online registration has reached its current limit. Please contact our team on WhatsApp for assistance.']);
        } catch (UniqueConstraintViolationException $exception) {
            if (! Registration::where('email', $registration->email)->exists()) {
                throw $exception;
            }

            throw ValidationException::withMessages(['email' => $request->messages()['email.unique']]);
        }

        $mailer->sendPending($registration);

        $request->session()->put('registration_receipt', [
            'reference' => $registration->reference,
            'full_name' => $registration->full_name,
            'email' => $registration->email,
            'email_sent' => $registration->acknowledgement_sent_at !== null,
        ]);

        return to_route('registrations.thanks');
    }

    public function thanks(Request $request): Response|RedirectResponse
    {
        $receipt = $request->session()->get('registration_receipt');

        if (! $receipt) {
            return to_route('registrations.create');
        }

        return response()->view('registrations.thanks', ['receipt' => $receipt])
            ->header('Cache-Control', 'private, no-store');
    }
}
