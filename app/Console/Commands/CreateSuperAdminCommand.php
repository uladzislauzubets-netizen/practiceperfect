<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('admin:create {--email= : Login email} {--first-name= : First name} {--last-name= : Last name}')]
#[Description('Create a Super Admin account (the only way the first admin exists)')]
class CreateSuperAdminCommand extends Command
{
    public function handle(): int
    {
        $data = [
            'first_name' => $this->option('first-name') ?: text('First name', required: true),
            'last_name' => $this->option('last-name') ?: text('Last name', required: true),
            'email' => $this->option('email') ?: text('Email', required: true),
            'password' => password('Password', required: true),
        ];

        $validator = Validator::make($data, [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => ['required', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::query()->create([
            ...$data,
            'role' => UserRole::SuperAdmin,
            'status' => UserStatus::Active,
            'email_verified_at' => now(),
        ]);

        $this->info("Super Admin {$data['email']} created.");

        return self::SUCCESS;
    }
}
