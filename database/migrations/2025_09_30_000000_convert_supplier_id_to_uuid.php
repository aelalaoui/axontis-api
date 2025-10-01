<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop foreign key if it exists
            $table->dropForeign(['supplier_id']);
            
            // Change supplier_id to uuid type
            $table->uuid('supplier_id')->change();
            
            // Re-add foreign key
            $table->foreign('supplier_id')
                  ->references('uuid')
                  ->on('suppliers')
                  ->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->unsignedBigInteger('supplier_id')->change();
            $table->foreign('supplier_id')
                  ->references('id')
                  ->on('suppliers')
                  ->onDelete('restrict');
        });
    }
};