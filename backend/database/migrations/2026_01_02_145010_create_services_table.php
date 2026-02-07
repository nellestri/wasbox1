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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Wash Only, Dry Only, Full Service
            $table->string('slug')->unique(); // wash, dry, full
            $table->text('description')->nullable();

            // Duration in minutes
            $table->integer('duration_minutes')->default(60);

            // Status
            $table->boolean('is_active')->default(true);

            // Display order
            $table->integer('display_order')->default(0);

            $table->timestamps();

            // Indexes
            $table->index('slug');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
