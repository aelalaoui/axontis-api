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
        Schema::create('arrivals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->decimal('ht_price', 10, 2); // Price excluding tax
            $table->decimal('tva_price', 10, 2); // VAT amount
            $table->decimal('ttc_price', 10, 2); // Price including tax
            $table->integer('qty'); // Quantity received
            $table->string('order_number')->nullable(); // Purchase order number
            $table->string('supplier')->nullable(); // Supplier name
            $table->date('arrival_date'); // Date of arrival
            $table->string('invoice_number')->nullable(); // Invoice reference
            $table->text('notes')->nullable(); // Additional notes
            $table->enum('status', ['pending', 'received', 'verified', 'stocked'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arrivals');
    }
};
