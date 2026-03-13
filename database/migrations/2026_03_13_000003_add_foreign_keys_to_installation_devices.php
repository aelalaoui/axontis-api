<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installation_devices', function (Blueprint $table) {
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
        Schema::table('installation_devices', function (Blueprint $table) {
            $table->dropForeign(['task_id']);
            $table->dropForeign(['device_id']);
        });
    }
};

