<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Users\SetPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PasswordChangeController extends Controller
{
    public function edit(Request $request): Response|RedirectResponse
    {
        if (! $request->user()->must_change_password) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/ChangePassword');
    }

    public function update(ChangePasswordRequest $request, SetPassword $setPassword): RedirectResponse
    {
        abort_unless($request->user()->must_change_password, 403);

        $setPassword->handle($request->user(), $request->string('password')->toString());

        return redirect()->route('dashboard')->with('success', 'Your password has been changed.');
    }
}
