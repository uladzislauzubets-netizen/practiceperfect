<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Users\CreateTrainerAccount;
use App\Actions\Users\DeactivateUser;
use App\Actions\Users\DeleteUser;
use App\Actions\Users\ReactivateUser;
use App\Actions\Users\UpdateUserAccount;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteUserRequest;
use App\Http\Requests\Admin\StoreTrainerRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\ImpersonationManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'in:'.implode(',', array_column(UserRole::cases(), 'value'))],
            'status' => ['nullable', 'in:'.implode(',', array_column(UserStatus::cases(), 'value'))],
        ]);

        $actor = $request->user();

        $users = User::query()
            ->with('trainerProfile')
            ->search($filters['q'] ?? null)
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('first_name')->orderBy('last_name')->orderBy('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'business_name' => $user->trainerProfile?->business_name,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'status' => $user->status->value,
                'status_label' => $user->status->label(),
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'can' => [
                    'update' => $actor->can('update', $user),
                    'impersonate' => $actor->can('impersonate', $user),
                    'deactivate' => $actor->can('deactivate', $user),
                    'reactivate' => $actor->can('reactivate', $user),
                    'delete' => $actor->can('delete', $user),
                ],
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => ['q' => $filters['q'] ?? '', 'role' => $filters['role'] ?? '', 'status' => $filters['status'] ?? ''],
            'roles' => collect(UserRole::cases())->map(fn (UserRole $role) => ['value' => $role->value, 'label' => $role->label()])->all(),
            'statuses' => collect(UserStatus::cases())->map(fn (UserStatus $status) => ['value' => $status->value, 'label' => $status->label()])->all(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('Admin/Users/Create', [
            'delivery_options' => [
                ['value' => CreateTrainerAccount::DELIVERY_INVITE_LINK, 'label' => 'Send an invitation email with a setup link'],
                ['value' => CreateTrainerAccount::DELIVERY_TEMPORARY_PASSWORD, 'label' => 'Generate a temporary password (must be changed on first login)'],
            ],
        ]);
    }

    public function store(StoreTrainerRequest $request, CreateTrainerAccount $create): RedirectResponse
    {
        $create->handle($request->user(), $request->safe()->only(['business_name', 'first_name', 'last_name', 'email', 'phone', 'delivery']));

        return redirect()->route('admin.users.index')->with('success', 'Trainer account created. The invitation email is on its way.');
    }

    public function edit(User $user): Response
    {
        Gate::authorize('update', $user);

        $user->load(['trainerProfile', 'coachProfile']);

        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'status_label' => $user->status->label(),
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'school' => $user->school,
                'business_name' => $user->trainerProfile?->business_name,
                'address' => $user->trainerProfile?->address,
                'website' => $user->trainerProfile?->website,
                'description' => $user->trainerProfile?->description,
                'bio' => $user->coachProfile?->bio,
                'credentials' => $user->coachProfile?->credentials,
                'certifications' => $user->coachProfile?->certifications,
                'is_public' => $user->coachProfile?->is_public ?? false,
            ],
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAccount $update): RedirectResponse
    {
        $update->handle($request->user(), $user, $request->validated());

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function deactivate(Request $request, User $user, DeactivateUser $deactivate): RedirectResponse
    {
        Gate::authorize('deactivate', $user);

        $deactivate->handle($request->user(), $user);

        return back()->with('success', "{$user->name} was deactivated.");
    }

    public function reactivate(Request $request, User $user, ReactivateUser $reactivate): RedirectResponse
    {
        Gate::authorize('reactivate', $user);

        $reactivate->handle($request->user(), $user);

        return back()->with('success', "{$user->name} was reactivated.");
    }

    public function destroy(DeleteUserRequest $request, User $user, DeleteUser $delete): RedirectResponse
    {
        $delete->handle($request->user(), $user, $request->string('reason')->toString());

        return redirect()->route('admin.users.index')->with('success', 'The user was deleted and their personal data anonymized.');
    }

    public function impersonate(Request $request, User $user, ImpersonationManager $impersonation): RedirectResponse
    {
        Gate::authorize('impersonate', $user);

        $impersonation->start($request->user(), $user);

        return redirect()->route('dashboard');
    }
}
