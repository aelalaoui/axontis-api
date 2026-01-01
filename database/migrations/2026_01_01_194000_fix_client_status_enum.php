<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, convert old enum values to valid new values
        // 'installation_step' and other old values should become 'created'
        DB::statement("UPDATE clients SET status = 'created' WHERE status IN ('installation_step', 'price_step', 'email_step')");

        // Now modify the enum to the new values
        $statusValues = \App\Enums\ClientStatus::values();
        $enumValues = implode("', '", $statusValues);

        DB::statement("ALTER TABLE clients MODIFY status enum('" . $enumValues . "') NOT NULL DEFAULT 'created'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to the old enum values
        DB::statement("ALTER TABLE clients MODIFY status enum('email_step','price_step','info_step','installation_step','document_step','signature_step','signed','payment_step','paid','create_password','active','not_active_due_payment','formal_notice','disabled') NOT NULL DEFAULT 'email_step'");
    }
};

