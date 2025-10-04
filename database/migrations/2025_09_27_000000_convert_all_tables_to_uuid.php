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
            // MySQL UUID generator
            DB::statement("UPDATE {$table} SET uuid = UUID();");
        }
    }

    public function down(): void
    {

    }
};
