<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old table (empty)
        Schema::dropIfExists('task_devices');

        // Create new clean table
        Schema::create('installation_devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->string('serial_number')->nullable();
            $table->enum('status', ['assigned', 'installed', 'returned', 'maintenance', 'replaced'])->default('assigned');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installation_devices');

        Schema::create('task_devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->decimal('ht_price', 10, 2);
            $table->decimal('tva_price', 10, 2);
            $table->decimal('ttc_price', 10, 2);
            $table->string('serial_number')->nullable();
            $table->string('inventory_number');
            $table->enum('status', ['assigned', 'installed', 'returned', 'maintenance', 'replaced'])->default('assigned');
            $table->date('assigned_date')->useCurrent();
            $table->date('installation_date')->nullable();
            $table->date('return_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
};

