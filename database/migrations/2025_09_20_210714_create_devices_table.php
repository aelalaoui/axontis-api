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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('brand');
            $table->string('model');
            $table->integer('stock_qty')->default(0);
            $table->string('category')->nullable(); // e.g., 'smartphone', 'tablet', 'laptop'
            $table->text('description')->nullable();
            $table->decimal('default_ht_price', 10, 2)->nullable(); // Default price excluding tax
            $table->decimal('default_tva_rate', 5, 2)->default(20.00); // Default VAT rate
            $table->boolean('is_active')->default(true);
            $table->integer('min_stock_level')->default(0); // Minimum stock alert level
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
