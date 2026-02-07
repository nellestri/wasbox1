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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // User who performed action
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            // Action type
            $table->string('action'); // created, updated, deleted, etc.

            // Entity
            $table->string('entity_type'); // Order, Customer, etc.
            $table->unsignedBigInteger('entity_id')->nullable();

            // Description
            $table->text('description');

            // Changes (JSON)
            $table->json('changes')->nullable();

            // IP address
            $table->string('ip_address', 45)->nullable();

            // User agent
            $table->string('user_agent')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('user_id');
            $table->index('action');
            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
