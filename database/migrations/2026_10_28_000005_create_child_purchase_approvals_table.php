<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_purchase_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_profile_id')->constrained('player_profiles')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->constrained('users')->cascadeOnDelete();
            $table->nullableMorphs('purchasable');
            $table->string('description');
            $table->unsignedBigInteger('amount');
            $table->string('payment_type', 20);
            $table->string('status', 20)->default('pending');
            $table->boolean('auto_approved')->default(false);
            $table->timestamp('requested_at');
            $table->timestamp('expires_at');
            $table->timestamp('responded_at')->nullable();
            $table->text('parent_notes')->nullable();
            $table->timestamp('info_requested_at')->nullable();
            $table->text('info_request_message')->nullable();
            $table->timestamps();
            $table->index(['parent_id', 'status']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_purchase_approvals');
    }
};
