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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique(); // Auto-generated order number
            $table->enum('type', ['locally', 'externally']); // Order type
            $table->enum('status', ['draft', 'pending', 'approved', 'ordered', 'partially_received', 'completed', 'cancelled'])->default('draft');
            
            // User relationships
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade'); // User who requested the order
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); // User who approved the order
            
            // Supplier relationship
            $table->foreignId('supplier_id')->nullable()->constrained()->onDelete('set null');
            
            // Quotation file
            $table->foreignId('quotation_file_id')->nullable()->constrained('files')->onDelete('set null');
            
            // Order details
            $table->date('order_date')->nullable(); // Date when order was placed
            $table->date('expected_delivery_date')->nullable();
            $table->decimal('total_ht', 12, 2)->default(0); // Total excluding tax
            $table->decimal('total_tva', 12, 2)->default(0); // Total VAT
            $table->decimal('total_ttc', 12, 2)->default(0); // Total including tax
            
            // Additional information
            $table->text('notes')->nullable();
            $table->string('priority', 20)->default('normal'); // low, normal, high, urgent
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};