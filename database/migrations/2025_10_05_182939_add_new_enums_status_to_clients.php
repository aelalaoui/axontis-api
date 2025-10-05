<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            DB::statement("ALTER TABLE clients MODIFY COLUMN status ENUM('email_step', 'price_step', 'info_step', 'document_step', 'signature_step', 'signed', 'payment_step', 'paid', 'create_password', 'active', 'not_active_due_payment', 'formal_notice', 'disabled') NOT NULL DEFAULT 'email_step'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            DB::statement("ALTER TABLE clients MODIFY COLUMN status ENUM('prospect', 'active_client', 'inactive_client') NOT NULL DEFAULT 'prospect'");
        });
    }
};
