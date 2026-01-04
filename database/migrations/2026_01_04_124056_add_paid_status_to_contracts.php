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
        Schema::table('contracts', function (Blueprint $table) {
            DB::statement("ALTER TABLE contracts MODIFY status enum('created', 'signed', 'paid', 'pending','active','suspended','terminated') NOT NULL DEFAULT 'created'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            DB::statement("ALTER TABLE contracts MODIFY status enum('pending','signed','active','suspended','terminated') NOT NULL DEFAULT 'pending'");
        });
    }
};
