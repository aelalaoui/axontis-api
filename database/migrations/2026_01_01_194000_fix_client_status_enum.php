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
        // Get the current status values from enum
        $statusValues = \App\Enums\ClientStatus::values();

        // Build the enum values string
        $enumValues = implode("', '", $statusValues);

        // Use raw SQL to modify the column
        DB::statement("ALTER TABLE clients MODIFY status enum('" . $enumValues . "') NOT NULL DEFAULT 'created'");

        // Then, update any existing rows with old enum values to 'created'
        DB::statement("UPDATE clients SET status = 'created' WHERE status NOT IN ('created', 'signed', 'refused', 'paid', 'active', 'formal_notice', 'disabled', 'closed')");
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

