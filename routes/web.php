<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SolicitationController;
use App\Http\Controllers\TeamingController;
use App\Http\Controllers\ThanksController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/past-performance', [PerformanceController::class, 'index'])->name('performance.index');
Route::get('/past-performance/{slug}', [PerformanceController::class, 'show'])->name('performance.show')->where('slug', '[a-z0-9-]+');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/capability-statement', [PageController::class, 'capability'])->name('capability');
Route::get('/capability-statement.pdf', [PageController::class, 'capabilityPdf'])->name('capability.pdf');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');

Route::get('/rfq', [SolicitationController::class, 'create'])->name('solicitations.create');
Route::get('/teaming', [TeamingController::class, 'create'])->name('teaming.create');
Route::get('/careers', [CareerController::class, 'index'])->name('careers.index');
Route::get('/careers/apply', [CareerController::class, 'create'])->name('careers.apply');
Route::get('/careers/{job}', [CareerController::class, 'show'])->name('careers.show');

Route::middleware('throttle:forms')->group(function () {
    Route::post('/rfq', [SolicitationController::class, 'store'])->name('solicitations.store');
    Route::post('/teaming', [TeamingController::class, 'store'])->name('teaming.store');
    Route::post('/careers/apply', [CareerController::class, 'store'])->name('careers.store');
    Route::post('/contact', [EnquiryController::class, 'store'])->name('contact.store');
});
Route::get('/thanks', ThanksController::class)->name('thanks');

// Signed links only: certificate renewals (emailed to vendors) and document rooms.
Route::middleware('signed')->group(function () {
    Route::get('/teaming/{vendor}/renew', [TeamingController::class, 'renew'])->name('teaming.renew');
    Route::post('/teaming/{vendor}/renew', [TeamingController::class, 'storeRenewal'])->middleware('throttle:forms')->name('teaming.renew.store');
    Route::get('/documents/{room}/{key}', [RoomController::class, 'show'])->name('rooms.show');
    Route::get('/documents/{room}/{key}/{document}', [RoomController::class, 'download'])->name('rooms.download');
});

Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/photo-credits', [PageController::class, 'credits'])->name('credits');
Route::get('/sitemap', [PageController::class, 'sitemapPage'])->name('sitemap.page');
Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');

// Pages on the firm's current website (environetinc.com) that may still be bookmarked or linked.
foreach ((array) config('environet.legacy_redirects', []) as $from => $to) {
    Route::permanentRedirect($from, $to);
}

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::middleware('area:solicitations')->group(function () {
        Route::get('/bids', [Admin\SolicitationController::class, 'index'])->name('solicitations.index');
        Route::get('/bids/export', [Admin\SolicitationController::class, 'export'])->name('solicitations.export');
        Route::get('/bids/{solicitation}', [Admin\SolicitationController::class, 'show'])->name('solicitations.show');
        Route::patch('/bids/{solicitation}', [Admin\SolicitationController::class, 'update'])->name('solicitations.update');
        Route::post('/bids/{solicitation}/notes', [Admin\SolicitationController::class, 'note'])->name('solicitations.note');
        Route::get('/bids/{solicitation}/files/{file}', [Admin\SolicitationController::class, 'file'])->name('solicitations.file');
    });

    Route::middleware('area:teaming')->group(function () {
        Route::get('/vendors', [Admin\VendorController::class, 'index'])->name('vendors.index');
        Route::get('/vendors/export', [Admin\VendorController::class, 'export'])->name('vendors.export');
        Route::get('/vendors/{vendor}', [Admin\VendorController::class, 'show'])->name('vendors.show');
        Route::patch('/vendors/{vendor}', [Admin\VendorController::class, 'update'])->name('vendors.update');
        Route::post('/vendors/{vendor}/notes', [Admin\VendorController::class, 'note'])->name('vendors.note');
        Route::get('/vendors/{vendor}/documents/{document}', [Admin\VendorController::class, 'document'])->name('vendors.document');
        Route::patch('/vendors/{vendor}/documents/{document}', [Admin\VendorController::class, 'verify'])->name('vendors.verify');
    });

    Route::middleware('area:careers')->group(function () {
        Route::get('/jobs', [Admin\JobPostingController::class, 'index'])->name('jobs.index');
        Route::get('/jobs/create', [Admin\JobPostingController::class, 'create'])->name('jobs.create');
        Route::post('/jobs', [Admin\JobPostingController::class, 'store'])->name('jobs.store');
        Route::get('/jobs/{job}/edit', [Admin\JobPostingController::class, 'edit'])->name('jobs.edit');
        Route::put('/jobs/{job}', [Admin\JobPostingController::class, 'update'])->name('jobs.update');
        Route::get('/applications', [Admin\ApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/export', [Admin\ApplicationController::class, 'export'])->name('applications.export');
        Route::get('/applications/{application}', [Admin\ApplicationController::class, 'show'])->name('applications.show');
        Route::patch('/applications/{application}', [Admin\ApplicationController::class, 'update'])->name('applications.update');
        Route::post('/applications/{application}/notes', [Admin\ApplicationController::class, 'note'])->name('applications.note');
        Route::get('/applications/{application}/cv', [Admin\ApplicationController::class, 'cv'])->name('applications.cv');
        Route::patch('/applications/{application}/certifications/{certification}', [Admin\ApplicationController::class, 'checkCertification'])->name('applications.certifications.check');
    });

    Route::middleware('area:enquiries')->group(function () {
        Route::get('/inquiries', [Admin\EnquiryController::class, 'index'])->name('enquiries.index');
        Route::get('/inquiries/{enquiry}', [Admin\EnquiryController::class, 'show'])->name('enquiries.show');
        Route::patch('/inquiries/{enquiry}', [Admin\EnquiryController::class, 'update'])->name('enquiries.update');
    });

    Route::middleware('area:rooms')->group(function () {
        Route::get('/rooms', [Admin\RoomController::class, 'index'])->name('rooms.index');
        Route::post('/rooms', [Admin\RoomController::class, 'store'])->name('rooms.store');
        Route::get('/rooms/{room}', [Admin\RoomController::class, 'show'])->name('rooms.show');
        Route::patch('/rooms/{room}', [Admin\RoomController::class, 'update'])->name('rooms.update');
        Route::post('/rooms/{room}/documents', [Admin\RoomController::class, 'upload'])->name('rooms.upload');
        Route::get('/rooms/{room}/documents/{document}', [Admin\RoomController::class, 'download'])->name('rooms.download');
        Route::delete('/rooms/{room}/documents/{document}', [Admin\RoomController::class, 'destroyDocument'])->name('rooms.documents.destroy');
        Route::post('/rooms/{room}/share', [Admin\RoomController::class, 'share'])->name('rooms.share');
        Route::post('/rooms/{room}/revoke', [Admin\RoomController::class, 'revoke'])->name('rooms.revoke');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::post('/users', [Admin\UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
    });
});
