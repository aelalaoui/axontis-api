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
        Schema::table('claims', function (Blueprint $table) {
            $table->foreignId('task_device_id')->nullable()->constrained('task_devices')->onDelete('set null');
            $table->enum('claim_type', ['warranty', 'maintenance', 'repair', 'replacement', 'other'])->default('other');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropForeign(['task_device_id']);
            $table->dropColumn(['task_device_id', 'claim_type']);
        });
    }
};
