<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $tables = [
            'users',
            'clients',
            'contracts',
            'tasks',
            'payments',
            'alerts',
            'claims',
            'files',
            'signatures',
            'communications',
            'devices',
            'arrivals',
            'task_devices',
            'suppliers',
            'orders',
            'order_device',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasColumn($table, 'uuid')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->uuid('uuid')->nullable()->after('id')->unique();
                });

                // MySQL UUID generator
                DB::statement("UPDATE {$table} SET uuid = UUID();");
            }
        }
    }

    public function down(): void
    {
        foreach ([
            'users','clients','contracts','tasks','payments','alerts','claims',
            'files','signatures','communications','devices','arrivals',
            'task_devices','suppliers','orders','order_device'
        ] as $table) {
            if (Schema::hasColumn($table, 'uuid')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('uuid');
                });
            }
        }
    }
};
