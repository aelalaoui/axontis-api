<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds client_uuid columns to contracts and alerts tables
     * for better security (using UUIDs instead of auto-increment IDs).
     */
    public function up(): void
    {
        // Add client_uuid to contracts table
        Schema::table('contracts', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->after('uuid');
            $table->index('client_uuid');
        });

        // Migrate existing data - copy uuid from clients based on client_id
        DB::statement('
            UPDATE contracts c
            INNER JOIN clients cl ON c.client_id = cl.id
            SET c.client_uuid = cl.uuid
        ');

        // Add client_uuid and contract_uuid to alerts table
        Schema::table('alerts', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->after('uuid');
            $table->uuid('contract_uuid')->nullable()->after('client_uuid');
            $table->index('client_uuid');
            $table->index('contract_uuid');
        });

        // Migrate existing data for alerts
        DB::statement('
            UPDATE alerts a
            INNER JOIN clients cl ON a.client_id = cl.id
            SET a.client_uuid = cl.uuid
        ');

        DB::statement('
            UPDATE alerts a
            INNER JOIN contracts c ON a.contract_id = c.id
            SET a.contract_uuid = c.uuid
        ');

        // Add contract_uuid to payments table if it uses contract_id
        if (Schema::hasColumn('payments', 'contract_id') && !Schema::hasColumn('payments', 'contract_uuid')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->uuid('contract_uuid')->nullable()->after('uuid');
                $table->index('contract_uuid');
            });

            DB::statement('
                UPDATE payments p
                INNER JOIN contracts c ON p.contract_id = c.id
                SET p.contract_uuid = c.uuid
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex(['client_uuid']);
            $table->dropColumn('client_uuid');
        });

        Schema::table('alerts', function (Blueprint $table) {
            $table->dropIndex(['client_uuid']);
            $table->dropIndex(['contract_uuid']);
            $table->dropColumn(['client_uuid', 'contract_uuid']);
        });

        if (Schema::hasColumn('payments', 'contract_uuid')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex(['contract_uuid']);
                $table->dropColumn('contract_uuid');
            });
        }
    }
};

