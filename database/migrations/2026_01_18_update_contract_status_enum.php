<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE contracts MODIFY status ENUM('created','signed','paid','pending','scheduled','active','suspended','terminated')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE contracts MODIFY status ENUM('created','signed','paid','pending','active','suspended','terminated')");
    }
};
