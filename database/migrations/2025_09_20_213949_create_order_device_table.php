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
        Schema::create('order_device', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->nullable()->constrained()->onDelete('set null'); // Can override order supplier
            
            // Pricing information
            $table->decimal('ht_price', 10, 2); // Price excluding tax per unit
            $table->decimal('tva_rate', 5, 2)->default(20.00); // VAT rate percentage
            $table->decimal('tva_price', 10, 2); // VAT amount per unit
            $table->decimal('ttc_price', 10, 2); // Price including tax per unit
            
            // Quantity information
            $table->integer('qty_ordered'); // Quantity ordered
            $table->integer('qty_received')->default(0); // Quantity received so far
            // Note: qty_pending will be calculated in the model as an accessor
            
            // Status and tracking
            $table->enum('status', ['pending', 'ordered', 'partially_received', 'received', 'cancelled'])->default('pending');
            $table->date('expected_delivery_date')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['order_id', 'device_id']);
            $table->index('supplier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_device');
    }
};