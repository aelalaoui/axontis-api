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
        Schema::table('arrivals', function (Blueprint $table) {
            // Index pour les requêtes par order_id
            $table->index('order_id');

            // Index pour les requêtes par device_id
            $table->index('device_id');

            // Index pour les requêtes par status
            $table->index('status');

            // Index pour les requêtes par arrival_date
            $table->index('arrival_date');

            // Index composé pour les requêtes fréquentes order_id + device_id
            $table->index(['order_id', 'device_id'], 'arrivals_order_device_index');

            // Index composé pour les requêtes par order_id + status
            $table->index(['order_id', 'status'], 'arrivals_order_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrivals', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
            $table->dropIndex(['device_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['arrival_date']);
            $table->dropIndex('arrivals_order_device_index');
            $table->dropIndex('arrivals_order_status_index');
        });
    }
};
