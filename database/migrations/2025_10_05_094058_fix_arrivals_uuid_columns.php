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
        Schema::table('arrivals', function (Blueprint $table) {
            // Modifier les colonnes pour qu'elles puissent stocker des UUIDs complets (36 caractères)
            $table->string('device_id', 36)->change();
            $table->string('order_id', 36)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            // Revenir à la taille précédente si nécessaire
            // Note: Vous devrez ajuster ces valeurs selon votre structure précédente
            $table->string('device_id', 255)->change();
            $table->string('order_id', 255)->change();
        });
    }
};
