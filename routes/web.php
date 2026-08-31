<?php

use App\Http\Controllers\LegalDocumentController;
use App\Http\Controllers\PartnerOnboardingController;
use App\Http\Controllers\PartnerRegistrationConfirmationController;
use App\Http\Controllers\PartnerRegistrationController;
use App\Http\Controllers\TenantSelectionController;
use App\Http\Middleware\EnsureActiveTenantContext;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/betrieb-auswaehlen', [TenantSelectionController::class, 'show'])
        ->name('tenant-selection.show');
    Route::post('/betrieb-auswaehlen', [TenantSelectionController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('tenant-selection.store');

    Route::middleware(EnsureActiveTenantContext::class)->group(function (): void {
        Route::get('/onboarding', [PartnerOnboardingController::class, 'show'])->name('onboarding.show');
        Route::post('/onboarding', [PartnerOnboardingController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('onboarding.store');
    });
});

Route::get('/nutzungsbedingungen', [LegalDocumentController::class, 'show'])
    ->defaults('document', 'terms')->name('legal.terms');
Route::get('/datenschutz', [LegalDocumentController::class, 'show'])
    ->defaults('document', 'privacy')->name('legal.privacy');

Route::middleware('guest')->group(function (): void {
    Route::get('/registrieren', [PartnerRegistrationController::class, 'create'])
        ->name('registration.create');
    Route::post('/registrieren', [PartnerRegistrationController::class, 'store'])
        ->middleware('throttle:partner-registration')
        ->name('registration.store');
    Route::get('/registrierung/ausstehend', [PartnerRegistrationController::class, 'pending'])
        ->name('registration.pending');

    Route::get(
        '/registrierung/bestaetigen/{intent}',
        [PartnerRegistrationConfirmationController::class, 'show'],
    )->middleware('throttle:registration-confirmation-view')->name('registration.confirm.show');

    Route::post(
        '/registrierung/bestaetigen/{intent}',
        [PartnerRegistrationConfirmationController::class, 'store'],
    )->middleware('throttle:registration-confirmation-submit')->name('registration.confirm.store');
});
