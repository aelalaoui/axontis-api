<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that must be InnoDB for FK support.
     * The old schema was created with MyISAM, so we convert them first.
     */
    private array $tablesToConvert = [
        'tasks',
        'devices',
        'installation_devices',
    ];

    public function up(): void
    {
        // Step 1 — Convert all involved tables to InnoDB
        foreach ($this->tablesToConvert as $table) {
            DB::statement("ALTER TABLE `{$table}` ENGINE = InnoDB");
        }

        // Step 2 — Add FK constraints now that InnoDB is in place
        Schema::table('installation_devices', function ($table) {
            $table->foreign('task_id')
                  ->references('id')
                  ->on('tasks')
                  ->cascadeOnDelete();

            $table->foreign('device_id')
                  ->references('id')
                  ->on('devices')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('installation_devices', function ($table) {
            $table->dropForeign(['task_id']);
            $table->dropForeign(['device_id']);
        });
    }
};

