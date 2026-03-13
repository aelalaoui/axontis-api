<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old table (empty) — safe even if already gone
        Schema::dropIfExists('task_devices');

        // Drop if partially created by a previous failed run
        Schema::dropIfExists('installation_devices');

        Schema::create('installation_devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('task_id');   // FK added in migration 000003
            $table->unsignedBigInteger('device_id'); // FK added in migration 000003
            $table->string('serial_number')->nullable();
            $table->enum('status', ['assigned', 'installed', 'returned', 'maintenance', 'replaced'])->default('assigned');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installation_devices');
    }
};
