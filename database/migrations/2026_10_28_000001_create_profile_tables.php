<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('business_name');
            $table->string('address')->nullable();
            $table->string('website', 2048)->nullable();
            $table->text('description')->nullable();
            $table->string('stripe_account_id')->nullable();
            $table->string('subscription_status')->nullable();
            $table->decimal('platform_fee_percent', 5, 2)->nullable();
            $table->string('brand_color', 7)->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('coach_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('active');
            $table->text('bio')->nullable();
            $table->text('credentials')->nullable();
            $table->text('certifications')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamp('joined_trainer_at')->nullable();
            $table->timestamps();
            $table->index(['trainer_id', 'status']);
        });

        Schema::create('player_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('skill_level')->nullable();
            $table->string('school')->nullable();
            $table->string('jersey_number', 10)->nullable();
            $table->boolean('is_child')->default(false);
            $table->string('photo_path')->nullable();
            $table->string('photo_thumb_path')->nullable();
            $table->boolean('allow_token_spending_without_approval')->default(false);
            $table->timestamps();
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_profiles');
        Schema::dropIfExists('coach_profiles');
        Schema::dropIfExists('trainer_profiles');
    }
};
