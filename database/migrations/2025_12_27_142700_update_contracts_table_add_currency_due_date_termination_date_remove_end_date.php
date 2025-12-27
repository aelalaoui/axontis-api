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
            // Ajouter currency avec valeur par défaut 'MAD'
            $table->string('currency', 3)->default('MAD')->after('vat_rate_percentage');

            // Ajouter due_date (format DD-MM, nullable)
            $table->string('due_date', 5)->nullable()->after('start_date');

            // Ajouter termination_date (nullable)
            $table->date('termination_date')->nullable()->after('status');

            // Supprimer end_date
            $table->dropColumn('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Restaurer end_date
            $table->date('end_date')->nullable()->after('start_date');

            // Supprimer les nouvelles colonnes
            $table->dropColumn(['currency', 'due_date', 'termination_date']);
        });
    }
};

