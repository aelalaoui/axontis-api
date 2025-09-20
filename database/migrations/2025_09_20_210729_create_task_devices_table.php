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
        Schema::create('task_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->decimal('ht_price', 10, 2); // Price excluding tax for this specific device
            $table->decimal('tva_price', 10, 2); // VAT amount
            $table->decimal('ttc_price', 10, 2); // Price including tax
            $table->string('serial_number')->nullable(); // Device serial number
            $table->string('inventory_number')->unique(); // Internal inventory tracking number
            $table->enum('status', ['assigned', 'installed', 'returned', 'maintenance', 'replaced'])->default('assigned');
            $table->date('assigned_date')->default(now());
            $table->date('installation_date')->nullable();
            $table->date('return_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Composite unique index to prevent duplicate device assignments to same task
            $table->unique(['task_id', 'device_id', 'inventory_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_devices');
    }
};
