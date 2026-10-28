<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\PlayerProfile;
use App\Models\User;
use App\Services\ContextResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(ContextResolver::class);
    }

    public function boot(): void
    {
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Relation::morphMap([
            'user' => User::class,
            'player_profile' => PlayerProfile::class,
        ]);

        Password::defaults(fn () => Password::min(8)->letters()->numbers());
    }
}
