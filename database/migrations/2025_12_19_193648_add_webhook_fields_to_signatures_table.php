<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('signatures', function (Blueprint $table) {
            $table->string('provider')->nullable()->after('metadata');
            $table->string('provider_envelope_id')->nullable()->index()->after('provider');
            $table->string('provider_status')->nullable()->after('provider_envelope_id');
            $table->json('webhook_payload')->nullable()->after('provider_status');
            $table->timestamp('webhook_received_at')->nullable()->after('webhook_payload');
            $table->text('signing_url')->nullable()->after('webhook_received_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signatures', function (Blueprint $table) {
            $table->dropColumn([
                'provider',
                'provider_envelope_id',
                'provider_status',
                'webhook_payload',
                'webhook_received_at',
                'signing_url',
            ]);
        });
    }
};
