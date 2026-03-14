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
            // Supprimer l'index (clé étrangère non contrainte) avant de dropper la colonne
            if (Schema::hasColumn('devices', 'installation_uuid')) {
                // Supprimer l'index si présent
                try {
                    $table->dropIndex('devices_installation_uuid_foreign');
                } catch (\Exception $e) {
                    // L'index n'existe peut-être pas
                }
                $table->dropColumn('installation_uuid');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->char('installation_uuid', 36)->nullable()->after('min_stock_level');
        });
    }
};
