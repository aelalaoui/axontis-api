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
            // Drop old decimal columns
            $table->dropColumn(['monthly_ht', 'monthly_tva', 'monthly_ttc']);

            // Add new integer columns (amounts in cents, vat in percentage)
            $table->unsignedBigInteger('monthly_amount_cents')->default(0)->after('status');
            $table->unsignedInteger('vat_rate_percentage')->default(20)->after('monthly_amount_cents');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn(['monthly_amount_cents', 'vat_rate_percentage']);

            // Restore old decimal columns
            $table->decimal('monthly_ht', 10, 2)->default(0)->after('status');
            $table->decimal('monthly_tva', 10, 2)->default(0)->after('monthly_ht');
            $table->decimal('monthly_ttc', 10, 2)->default(0)->after('monthly_tva');
        });
    }
};

