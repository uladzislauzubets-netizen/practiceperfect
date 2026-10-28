<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\BrandingResolver;
use App\Services\ContextResolver;
use App\Services\ImpersonationManager;
use App\Support\Media;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(
        private readonly BrandingResolver $branding,
        private readonly ContextResolver $contexts,
        private readonly ImpersonationManager $impersonation,
    ) {}

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => fn () => $user === null ? null : [
                'id' => $user->id,
                'name' => $user->name,
                'first_name' => $user->first_name,
                'email' => $user->email,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'is_child' => $user->isChildAccount(),
                'avatar_url' => $user->photo_thumb_path !== null ? Media::photoUrl($user->photo_thumb_path) : $user->photo_url,
                'email_verified' => $user->hasVerifiedEmail(),
                'unread_notifications' => $user->unreadNotifications()->count(),
            ],
            'branding' => fn () => $this->branding->forUser($user),
            'context' => fn () => $user?->isPlayer() ? $this->contexts->forFrontend($user) : null,
            'impersonation' => function () {
                $log = $this->impersonation->activeLog();

                return $log === null ? null : [
                    'target_name' => $log->impersonated->name,
                    'target_role' => $log->impersonated->role->label(),
                    'expires_at' => $log->expires_at->toIso8601String(),
                ];
            },
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'app_name' => config('app.name'),
        ];
    }
}
