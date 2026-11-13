<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CheckInRequest;
use App\Models\Registration;
use App\PaymentStatus;
use App\RegistrationStatus;
use App\Services\RegistrationCheckInService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CheckInController extends Controller
{
    public function index(): View
    {
        return view('admin.check-in', [
            'checkedIn' => Registration::whereNotNull('checked_in_at')->count(),
            'paidCount' => Registration::where('payment_status', PaymentStatus::Confirmed)->whereNotNull('payment_confirmed_at')->where('status', '!=', RegistrationStatus::Cancelled)->count(),
            'recent' => Registration::whereNotNull('checked_in_at')->latest('checked_in_at')->latest('id')->limit(6)->get(),
        ]);
    }

    public function verify(CheckInRequest $request, RegistrationCheckInService $checkIn): JsonResponse
    {
        return response()->json($checkIn->verify($request->validated('code')));
    }

    public function store(CheckInRequest $request, RegistrationCheckInService $checkIn): JsonResponse
    {
        $result = $checkIn->checkIn($request->validated('code'), (string) config('admin.username'));

        if ($result['state'] === 'checked_in') {
            $result['checked_in_total'] = Registration::whereNotNull('checked_in_at')->count();
        }

        return response()->json($result, $result['state'] === 'checked_in' ? 200 : 409);
    }
}
