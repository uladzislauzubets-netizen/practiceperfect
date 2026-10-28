<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Users\SetPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SetupPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PasswordSetupController extends Controller
{
    public function show(User $user): Response
    {
        $this->assertPending($user);

        return Inertia::render('Auth/SetupPassword', ['name' => $user->first_name]);
    }

    public function store(SetupPasswordRequest $request, User $user, SetPassword $setPassword): RedirectResponse
    {
        $this->assertPending($user);

        $setPassword->handle($user, $request->string('password')->toString());

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Your account is ready.');
    }

    private function assertPending(User $user): void
    {
        abort_unless($user->must_change_password && $user->isActive(), 403, 'This setup link has already been used.');
    }
}
