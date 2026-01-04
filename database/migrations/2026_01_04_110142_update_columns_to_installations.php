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
        Schema::table('installations', function (Blueprint $table) {
            $table->dropColumn('city');
            $table->dropColumn('zip_code');
            $table->integer('city_id')->nullable();
            $table->string('country_code', 2);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installations', function (Blueprint $table) {
            $table->string('city')->nullable();
            $table->string('zip_code')->nullable();
            $table->dropColumn('city_id');
            $table->dropColumn('country_code');
        });
    }
};
