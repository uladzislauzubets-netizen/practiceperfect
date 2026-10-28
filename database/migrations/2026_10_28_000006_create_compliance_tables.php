<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coach_availability_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('overridden_by')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->timestamps();
            $table->index('event_id');
        });

        Schema::create('user_deletions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_user_id')->index();
            $table->string('original_email');
            $table->foreignId('deleted_by')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->text('original_data');
            $table->timestamp('deleted_at');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64)->index();
            $table->nullableMorphs('subject');
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at');
        });

        Schema::create('camp_conversion_prefills', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('share_link_id')->constrained()->cascadeOnDelete();
            $table->json('payload');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('invitation_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camp_conversion_prefills');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('user_deletions');
        Schema::dropIfExists('coach_availability_overrides');
    }
};
