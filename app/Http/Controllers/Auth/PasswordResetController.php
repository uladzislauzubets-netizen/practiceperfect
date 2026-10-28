<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Users\SetPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetController extends Controller
{
    public function request(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function email(ForgotPasswordRequest $request): RedirectResponse
    {
        $user = User::query()->where('email', Str::lower($request->string('email')->toString()))->first();

        if ($user?->isActive()) {
            Password::sendResetLink(['email' => $user->email]);
        }

        return back()->with('success', 'If that email belongs to an account, a reset link is on its way. It expires in 1 hour.');
    }

    public function create(string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', ['token' => $token, 'email' => request()->query('email')]);
    }

    public function update(ResetPasswordRequest $request, SetPassword $setPassword): RedirectResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use ($setPassword) {
                $setPassword->handle($user, $password);
                $user->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return redirect()->route('login')->with('success', 'Your password has been reset. You can sign in now.');
    }
}
