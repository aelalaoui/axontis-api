<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('order_device', function (Blueprint $table) {
            // Rename columns to match UUID convention
            $table->renameColumn('order_id', 'order_uuid');
            $table->renameColumn('device_id', 'device_uuid');
        });
    }

    public function down()
    {
        Schema::table('order_device', function (Blueprint $table) {
            $table->renameColumn('order_uuid', 'order_id');
            $table->renameColumn('device_uuid', 'device_id');
        });
    }
};