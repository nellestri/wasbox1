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
        Schema::create('customer_ratings', function (Blueprint $table) {
            $table->id();

            // Order
            $table->foreignId('order_id')->constrained()->onDelete('cascade');

            // Customer
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');

            // Branch
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');

            // Rating (1-5 stars)
            $table->tinyInteger('rating')->unsigned();

            // Feedback
            $table->text('comment')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('order_id');
            $table->index('customer_id');
            $table->index('branch_id');
            $table->index('rating');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_ratings');
    }
};
