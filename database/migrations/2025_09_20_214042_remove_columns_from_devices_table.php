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
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'default_ht_price',
                'default_tva_rate', // Note: user mentioned default_tva_price but the table has default_tva_rate
                'is_active'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->decimal('default_ht_price', 10, 2)->nullable();
            $table->decimal('default_tva_rate', 5, 2)->default(20.00);
            $table->boolean('is_active')->default(true);
        });
    }
};