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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();

            // Template key
            $table->string('key')->unique();

            // Notification type
            $table->enum('type', [
                'order_received',
                'order_ready',
                'order_completed',
                'pickup_accepted',
                'pickup_en_route',
                'pickup_completed',
                'unclaimed_day3',
                'unclaimed_day5',
                'unclaimed_day7',
                'general'
            ]);

            // Content (supports variables like {{tracking_number}}, {{customer_name}})
            $table->string('title');
            $table->text('body');

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index('key');
            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
