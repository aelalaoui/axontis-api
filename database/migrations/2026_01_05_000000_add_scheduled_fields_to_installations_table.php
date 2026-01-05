<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installations', function (Blueprint $table) {
            // Modifier la colonne type en enum
            $table->enum('type', ['first_installation', 'curative', 'scheduled'])
                ->after('country')
                ->default('first_installation');

            // Ajouter les colonnes scheduled_date et scheduled_time
            $table->date('scheduled_date')->nullable()->after('type');
            $table->time('scheduled_time')->nullable()->after('scheduled_date');
            $table->dropColumn(['country_code']);
        });
    }

    public function down(): void
    {
        Schema::table('installations', function (Blueprint $table) {
            $table->dropColumn(['scheduled_date', 'scheduled_time', 'type']);
        });
    }
};

