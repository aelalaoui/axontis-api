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
        // Get the current status values from enum
        $statusValues = \App\Enums\ClientStatus::values();

        Schema::table('clients', function (Blueprint $table) use ($statusValues) {
            // Change the status column to use the correct enum values
            $table->enum('status', $statusValues)
                ->default('created')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to the old enum values
        Schema::table('clients', function (Blueprint $table) {
            $table->enum('status', ['email_step','price_step','info_step','installation_step','document_step','signature_step','signed','payment_step','paid','create_password','active','not_active_due_payment','formal_notice','disabled'])
                ->default('email_step')
                ->change();
        });
    }
};

