<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Check if a foreign key exists on a table
     */
    private function foreignKeyExists(string $table, string $foreignKey): bool
    {
        $database = config('database.connections.mysql.database');

        $result = DB::select("
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND CONSTRAINT_NAME = ?
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$database, $table, $foreignKey]);

        return count($result) > 0;
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $database = config('database.connections.mysql.database');

        $result = DB::select("
            SELECT INDEX_NAME
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND INDEX_NAME = ?
        ", [$database, $table, $indexName]);

        return count($result) > 0;
    }

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
            // Drop foreign key if exists
            if ($this->foreignKeyExists('contracts', 'contracts_client_id_foreign')) {
                Schema::table('contracts', function (Blueprint $table) {
                    $table->dropForeign(['client_id']);
                });
            }

            // Drop index if exists
            if ($this->indexExists('contracts', 'contracts_client_id_index')) {
                Schema::table('contracts', function (Blueprint $table) {
                    $table->dropIndex(['client_id']);
                });
            }

            // Drop the column
            Schema::table('contracts', function (Blueprint $table) {
                $table->dropColumn('client_id');
            });
        }

        // Remove client_id from alerts table
        if (Schema::hasColumn('alerts', 'client_id')) {
            if ($this->foreignKeyExists('alerts', 'alerts_client_id_foreign')) {
                Schema::table('alerts', function (Blueprint $table) {
                    $table->dropForeign(['client_id']);
                });
            }

            if ($this->indexExists('alerts', 'alerts_client_id_index')) {
                Schema::table('alerts', function (Blueprint $table) {
                    $table->dropIndex(['client_id']);
                });
            }

            Schema::table('alerts', function (Blueprint $table) {
                $table->dropColumn('client_id');
            });
        }

        // Remove contract_id from alerts table
        if (Schema::hasColumn('alerts', 'contract_id')) {
            if ($this->foreignKeyExists('alerts', 'alerts_contract_id_foreign')) {
                Schema::table('alerts', function (Blueprint $table) {
                    $table->dropForeign(['contract_id']);
                });
            }

            if ($this->indexExists('alerts', 'alerts_contract_id_index')) {
                Schema::table('alerts', function (Blueprint $table) {
                    $table->dropIndex(['contract_id']);
                });
            }

            Schema::table('alerts', function (Blueprint $table) {
                $table->dropColumn('contract_id');
            });
        }

        // Remove contract_id from payments table
        if (Schema::hasColumn('payments', 'contract_id')) {
            if ($this->foreignKeyExists('payments', 'payments_contract_id_foreign')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->dropForeign(['contract_id']);
                });
            }

            if ($this->indexExists('payments', 'payments_contract_id_index')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->dropIndex(['contract_id']);
                });
            }

            Schema::table('payments', function (Blueprint $table) {
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

        // Restore client_id to alerts
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

        // Restore contract_id to alerts
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

