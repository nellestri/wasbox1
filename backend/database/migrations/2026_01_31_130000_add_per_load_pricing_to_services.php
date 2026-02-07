<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->enum('pricing_type', ['per_kg', 'per_load'])->default('per_kg')->after('price_per_kg');
            $table->decimal('price_per_load', 8, 2)->nullable()->after('pricing_type');
        });
    }

    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['pricing_type', 'price_per_load']);
        });
    }
};
