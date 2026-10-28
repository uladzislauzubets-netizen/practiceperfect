<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\PasswordSetupController;
use App\Http\Controllers\Coach\MyTimesController;
use App\Http\Controllers\ContextController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Family\ApprovalController;
use App\Http\Controllers\Family\AvailabilityController;
use App\Http\Controllers\Family\ChildController;
use App\Http\Controllers\Family\FamilyController;
use App\Http\Controllers\Family\PurchaseRequestController;
use App\Http\Controllers\JoinController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Trainer\BrandingController;
use App\Http\Controllers\Trainer\CoachAssignmentController;
use App\Http\Controllers\Trainer\CoachController;
use App\Http\Controllers\Trainer\PlayerController;
use App\Http\Controllers\Trainer\ShareLinkController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

Route::get('/media/logo/{filename}', [MediaController::class, 'logo'])->name('media.logo');
Route::get('/media/photo/{filename}', [MediaController::class, 'photo'])->middleware('auth')->name('media.photo');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:20,1');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::middleware('signed')->group(function () {
    Route::get('/setup-password/{user}', [PasswordSetupController::class, 'show'])->name('password.setup');
    Route::post('/setup-password/{user}', [PasswordSetupController::class, 'store'])->name('password.setup.store');
});

Route::get('/join/{code}', [JoinController::class, 'show'])->name('join.show');
Route::post('/join/{code}/register', [JoinController::class, 'register'])->middleware(['guest', 'throttle:10,1'])->name('join.register');
Route::post('/join/{code}/register-coach', [JoinController::class, 'registerCoach'])->middleware(['guest', 'throttle:10,1'])->name('join.register-coach');

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])->middleware('throttle:6,1')->name('verification.send');

    Route::get('/password/change', [PasswordChangeController::class, 'edit'])->name('password.change');
    Route::post('/password/change', [PasswordChangeController::class, 'update'])->name('password.change.store');

    Route::post('/impersonation/stop', [ImpersonationController::class, 'stop'])->name('impersonation.stop');

    Route::post('/join/{code}/associate', [JoinController::class, 'associate'])->name('join.associate');
    Route::post('/join/{code}/accept', [JoinController::class, 'accept'])->name('join.accept');

    Route::middleware(['password.changed', 'verified'])->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

        Route::prefix('admin')->name('admin.')->middleware('role:super_admin')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::post('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
            Route::post('/users/{user}/reactivate', [UserController::class, 'reactivate'])->name('users.reactivate');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
            Route::post('/users/{user}/impersonate', [UserController::class, 'impersonate'])->name('users.impersonate');
            Route::get('/impersonation-history', [ImpersonationController::class, 'history'])->name('impersonation.history');
            Route::get('/impersonation-history/{log}', [ImpersonationController::class, 'show'])->name('impersonation.show');
        });

        Route::prefix('trainer')->name('trainer.')->middleware('role:trainer')->group(function () {
            Route::get('/players', [PlayerController::class, 'index'])->name('players.index');
            Route::patch('/players/{profile}', [PlayerController::class, 'updateSkill'])->name('players.skill');
            Route::delete('/players/{profile}', [PlayerController::class, 'destroy'])->name('players.destroy');

            Route::get('/coaches', [CoachController::class, 'index'])->name('coaches.index');
            Route::post('/coaches/invitations', [CoachController::class, 'invite'])->name('coaches.invite');
            Route::post('/coaches/invitations/{link}/resend', [CoachController::class, 'resend'])->name('coaches.resend');
            Route::delete('/coaches/{coach}', [CoachController::class, 'destroy'])->name('coaches.destroy');

            Route::post('/coach-assignments/check', [CoachAssignmentController::class, 'check'])->name('coach-assignments.check');
            Route::post('/coach-assignments/override', [CoachAssignmentController::class, 'override'])->name('coach-assignments.override');

            Route::get('/share-link', [ShareLinkController::class, 'show'])->name('share-link');

            Route::get('/branding', [BrandingController::class, 'edit'])->name('branding.edit');
            Route::post('/branding', [BrandingController::class, 'update'])->name('branding.update');
            Route::post('/branding/reset', [BrandingController::class, 'reset'])->name('branding.reset');
        });

        Route::prefix('coach')->name('coach.')->middleware('role:coach')->group(function () {
            Route::get('/my-times', [MyTimesController::class, 'edit'])->name('times.edit');
            Route::put('/my-times', [MyTimesController::class, 'update'])->name('times.update');
        });

        Route::middleware('role:player')->group(function () {
            Route::post('/context', [ContextController::class, 'switch'])->name('context.switch');

            Route::get('/availability', [AvailabilityController::class, 'edit'])->name('availability.edit');
            Route::put('/availability', [AvailabilityController::class, 'update'])->name('availability.update');

            Route::get('/purchase-requests', [PurchaseRequestController::class, 'index'])->name('purchase-requests.index');
            Route::post('/purchase-requests', [PurchaseRequestController::class, 'store'])->name('purchase-requests.store');

            Route::prefix('family')->name('family.')->middleware('not-child')->group(function () {
                Route::get('/', [FamilyController::class, 'index'])->name('index');
                Route::post('/self', [FamilyController::class, 'storeSelf'])->name('self.store');
                Route::post('/profiles', [ChildController::class, 'store'])->name('profiles.store');
                Route::post('/profiles/{profile}', [ChildController::class, 'update'])->name('profiles.update');
                Route::post('/profiles/{profile}/trainers', [ChildController::class, 'addTrainer'])->name('profiles.trainers.store');
                Route::delete('/profiles/{profile}/trainers/{trainer}', [ChildController::class, 'removeTrainer'])->name('profiles.trainers.destroy');
                Route::post('/profiles/{profile}/login', [ChildController::class, 'createLogin'])->name('profiles.login.store');
                Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
                Route::post('/approvals/{approval}', [ApprovalController::class, 'respond'])->name('approvals.respond');
            });
        });
    });
});
