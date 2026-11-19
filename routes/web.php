<?php

use App\Http\Controllers\Admin\CheckInController;
use App\Http\Controllers\Admin\RegistrationCertificateController;
use App\Http\Controllers\Admin\RegistrationPassController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RegistrationRecoveryController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::view('/program-schedule', 'program-schedule')->name('program-schedule');
Route::get('/register', [RegistrationController::class, 'create'])->name('registrations.create');
Route::post('/register', [RegistrationController::class, 'store'])
    ->middleware('throttle:5,1')->name('registrations.store');
Route::post('/register/check-email', [RegistrationRecoveryController::class, 'check'])->middleware('throttle:registration-email-check')->name('registrations.check-email');
Route::get('/registration-id', [RegistrationRecoveryController::class, 'create'])->name('registrations.recover');
Route::post('/registration-id', [RegistrationRecoveryController::class, 'send'])->middleware('throttle:registration-recovery')->name('registrations.recover.send');
Route::get('/register/thank-you', [RegistrationController::class, 'thanks'])->name('registrations.thanks');
Route::get('/register/payment/{reference}', fn () => to_route('registrations.recover'));

Route::prefix('admin')->name('admin.')->middleware('admin.headers')->group(function (): void {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:admin-login')->name('login.store');
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
    Route::middleware('admin.session')->group(function (): void {
        Route::get('/check-in', [CheckInController::class, 'index'])->name('check-in.index');
        Route::post('/check-in/verify', [CheckInController::class, 'verify'])->middleware('throttle:admin-check-in')->name('check-in.verify');
        Route::post('/check-in', [CheckInController::class, 'store'])->middleware('throttle:admin-check-in')->name('check-in.store');
        Route::get('/', [App\Http\Controllers\Admin\RegistrationController::class, 'index'])->name('dashboard');
        Route::get('/registrations/export', [App\Http\Controllers\Admin\RegistrationController::class, 'export'])->middleware('throttle:admin-export')->name('registrations.export');
        Route::get('/registrations/{registration}/certificate', [RegistrationCertificateController::class, 'show'])->name('registrations.certificate');
        Route::post('/registrations/{registration}/certificate', [RegistrationCertificateController::class, 'store'])->middleware('throttle:admin-emails')->name('registrations.certificate.send');
        Route::get('/registrations/{registration}/certificate/download', [RegistrationCertificateController::class, 'download'])->name('registrations.certificate.download');
        Route::get('/registrations/{registration}/pass/manage', [RegistrationPassController::class, 'show'])->name('registrations.pass');
        Route::post('/registrations/{registration}/pass', [RegistrationPassController::class, 'sendPass'])->middleware('throttle:admin-emails')->name('registrations.pass.send');
        Route::get('/registrations/{registration}/pass', [RegistrationPassController::class, 'download'])->name('registrations.pass.download');
        Route::get('/registrations/{registration}', [App\Http\Controllers\Admin\RegistrationController::class, 'show'])->name('registrations.show');
        Route::put('/registrations/{registration}', [App\Http\Controllers\Admin\RegistrationController::class, 'update'])->name('registrations.update');
        Route::delete('/registrations/{registration}', [App\Http\Controllers\Admin\RegistrationController::class, 'destroy'])->name('registrations.destroy');
        Route::post('/registrations/{registration}/emails', [App\Http\Controllers\Admin\RegistrationController::class, 'sendPendingEmails'])->middleware('throttle:admin-emails')->name('registrations.emails');
    });
});
