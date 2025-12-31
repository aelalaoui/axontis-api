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
     * This migration removes the old ID-based foreign keys (client_id, contract_id)
     * now that we're using UUID-based foreign keys for better security.
     */
    public function up(): void
    {
        // Remove client_id from contracts table
        if (Schema::hasColumn('contracts', 'client_id')) {
            Schema::table('contracts', function (Blueprint $table) {
                // Try to drop foreign key if exists
                try {
                    $table->dropForeign(['client_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }

                // Try to drop index if exists
                try {
                    $table->dropIndex(['client_id']);
                } catch (\Exception $e) {
                    // Index might not exist
                }

                $table->dropColumn('client_id');
            });
        }

        // Remove client_id and contract_id from alerts table
        if (Schema::hasColumn('alerts', 'client_id')) {
            Schema::table('alerts', function (Blueprint $table) {
                try {
                    $table->dropForeign(['client_id']);
                } catch (\Exception $e) {}

                try {
                    $table->dropIndex(['client_id']);
                } catch (\Exception $e) {}

                $table->dropColumn('client_id');
            });
        }

        if (Schema::hasColumn('alerts', 'contract_id')) {
            Schema::table('alerts', function (Blueprint $table) {
                try {
                    $table->dropForeign(['contract_id']);
                } catch (\Exception $e) {}

                try {
                    $table->dropIndex(['contract_id']);
                } catch (\Exception $e) {}

                $table->dropColumn('contract_id');
            });
        }

        // Remove contract_id from payments table
        if (Schema::hasColumn('payments', 'contract_id')) {
            Schema::table('payments', function (Blueprint $table) {
                try {
                    $table->dropForeign(['contract_id']);
                } catch (\Exception $e) {}

                try {
                    $table->dropIndex(['contract_id']);
                } catch (\Exception $e) {}

                $table->dropColumn('contract_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore client_id to contracts
        if (!Schema::hasColumn('contracts', 'client_id')) {
            Schema::table('contracts', function (Blueprint $table) {
                $table->unsignedBigInteger('client_id')->nullable()->after('uuid');
                $table->index('client_id');
            });

            // Migrate data back from client_uuid
            DB::statement('
                UPDATE contracts c
                INNER JOIN clients cl ON c.client_uuid = cl.uuid
                SET c.client_id = cl.id
            ');
        }

        // Restore client_id and contract_id to alerts
        if (!Schema::hasColumn('alerts', 'client_id')) {
            Schema::table('alerts', function (Blueprint $table) {
                $table->unsignedBigInteger('client_id')->nullable()->after('uuid');
                $table->index('client_id');
            });

            DB::statement('
                UPDATE alerts a
                INNER JOIN clients cl ON a.client_uuid = cl.uuid
                SET a.client_id = cl.id
            ');
        }

        if (!Schema::hasColumn('alerts', 'contract_id')) {
            Schema::table('alerts', function (Blueprint $table) {
                $table->unsignedBigInteger('contract_id')->nullable()->after('client_id');
                $table->index('contract_id');
            });

            DB::statement('
                UPDATE alerts a
                INNER JOIN contracts c ON a.contract_uuid = c.uuid
                SET a.contract_id = c.id
            ');
        }

        // Restore contract_id to payments
        if (!Schema::hasColumn('payments', 'contract_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->unsignedBigInteger('contract_id')->nullable()->after('uuid');
                $table->index('contract_id');
            });

            DB::statement('
                UPDATE payments p
                INNER JOIN contracts c ON p.contract_uuid = c.uuid
                SET p.contract_id = c.id
            ');
        }
    }
};

