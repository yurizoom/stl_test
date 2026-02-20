<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slot_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['held', 'confirmed', 'cancelled'])->default('held');
            $table->uuid('idempotency_key');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['slot_id', 'idempotency_key']);
            $table->index(['slot_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holds');
    }
};
