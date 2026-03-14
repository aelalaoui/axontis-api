<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove the wrong column from products
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['contract_uuid']);
            $table->dropColumn('contract_uuid');
        });

        // Add product_uuid to contracts (a parent product can have many contracts)
        Schema::table('contracts', function (Blueprint $table) {
            $table->uuid('product_uuid')->nullable()->after('client_uuid');
            $table->index('product_uuid');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex(['product_uuid']);
            $table->dropColumn('product_uuid');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->uuid('contract_uuid')->nullable()->after('device_uuid');
            $table->index('contract_uuid');
        });
    }
};

