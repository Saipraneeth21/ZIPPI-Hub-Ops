<?php

use App\Http\Controllers\Admin\KycDocumentController;
use App\Http\Controllers\Admin\TrackingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Signed, short-TTL streaming of KYC documents for admin reviewers.
Route::get('/admin/kyc/document/{document}', KycDocumentController::class)
    ->middleware(['signed', 'auth:admin'])
    ->name('admin.kyc.document');

// Live fleet positions feed for the admin tracking map.
Route::get('/admin/tracking/positions', [TrackingController::class, 'positions'])
    ->middleware(['auth:admin'])
    ->name('admin.tracking.positions');

// Account → Logout. Sidebar link (GET) that ends the admin session.
Route::get('/admin/account/logout', function (Request $request) {
    Auth::guard('admin')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('filament.admin.auth.login');
})->middleware(['auth:admin'])->name('admin.account.logout');

// Hub Ops Account → Logout. Sidebar link (GET) that ends the hub-staff session.
Route::get('/hub/account/logout', function (Request $request) {
    Auth::guard('hub')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('filament.hub.auth.login');
})->middleware(['auth:hub'])->name('hub.account.logout');
