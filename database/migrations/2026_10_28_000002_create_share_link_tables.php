<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('share_links', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('type', 20);
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('target_email')->nullable();
            $table->string('invitee_name')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('uses_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['trainer_id', 'type']);
        });

        Schema::create('trainer_player', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('player_profile_id')->constrained('player_profiles')->cascadeOnDelete();
            $table->foreignId('share_link_id')->nullable()->constrained('share_links')->nullOnDelete();
            $table->string('status', 20)->default('active');
            $table->timestamp('connected_at');
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamps();
            $table->unique(['trainer_id', 'player_profile_id']);
        });

        Schema::create('share_link_uses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('share_link_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('player_profile_id')->nullable()->constrained('player_profiles')->nullOnDelete();
            $table->timestamp('used_at');
            $table->index('share_link_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('share_link_uses');
        Schema::dropIfExists('trainer_player');
        Schema::dropIfExists('share_links');
    }
};
