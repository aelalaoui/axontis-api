<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('order_device', function (Blueprint $table) {
            // Drop existing foreign keys if they exist
            $table->dropForeign(['order_uuid']);
            $table->dropForeign(['device_uuid']);
            $table->dropForeign(['supplier_id']);
            
            // Change column types to UUID
            $table->uuid('order_uuid')->change();
            $table->uuid('device_uuid')->change();
            $table->uuid('supplier_id')->change();
            
            // Re-add foreign keys
            $table->foreign('order_uuid')
                  ->references('uuid')
                  ->on('orders')
                  ->onDelete('cascade');
                  
            $table->foreign('device_uuid')
                  ->references('uuid')
                  ->on('devices')
                  ->onDelete('cascade');
                  
            $table->foreign('supplier_id')
                  ->references('uuid')
                  ->on('suppliers')
                  ->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::table('order_device', function (Blueprint $table) {
            $table->dropForeign(['order_uuid']);
            $table->dropForeign(['device_uuid']);
            $table->dropForeign(['supplier_id']);
            
            $table->unsignedBigInteger('order_uuid')->change();
            $table->unsignedBigInteger('device_uuid')->change();
            $table->unsignedBigInteger('supplier_id')->change();
        });
    }
};