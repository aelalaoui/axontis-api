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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('id_parent')->nullable();
            $table->string('name')->nullable()->index();
            $table->string('property_name')->nullable()->index();
            $table->string('default_value')->nullable();
            $table->float('caution_price')->nullable();
            $table->float('subscription_price')->nullable();
            $table->uuid('device_uuid')->nullable();
            $table->foreign('device_uuid')->references('uuid')->on('devices')->onDelete('cascade');
            $table->foreign('id_parent')->references('id')->on('products')->onDelete('cascade');

            $table->index('device_uuid');
            $table->index('id_parent');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['device_uuid']);
            $table->dropForeign(['id_parent']);
            $table->dropIndex(['device_uuid']);
            $table->dropIndex(['id_parent']);
            $table->dropIndex(['name']);
            $table->dropIndex(['property_name']);
        });
        Schema::dropIfExists('products');
    }
};
