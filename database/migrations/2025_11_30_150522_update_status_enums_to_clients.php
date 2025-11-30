<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ClientStatus;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->enum('status', ClientStatus::values())
                ->default(ClientStatus::EMAIL_STEP)
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $oldValues = [
                'email_step',
                'price_step',
                'info_step',
                'document_step',
                'signature_step',
                'signed',
                'payment_step',
                'paid',
                'create_password',
                'active',
                'not_active_due_payment',
                'formal_notice',
                'disabled',
            ];

            $table->enum('status', $oldValues)
                ->default('email_step')
                ->change();
        });
    }
};
