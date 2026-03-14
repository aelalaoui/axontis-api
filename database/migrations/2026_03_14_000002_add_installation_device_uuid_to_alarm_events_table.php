<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alarm_events', function (Blueprint $table) {
            // Nouvelle colonne — nullable pour rétro-compatibilité avec les événements existants
            // Insérée juste après device_uuid pour garder la cohérence visuelle
            $table->uuid('installation_device_uuid')
                  ->nullable()
                  ->after('device_uuid');

            // FK vers installation_devices.uuid — SET NULL si l'unité est supprimée
            // (préférable à CASCADE pour conserver l'historique des événements)
            $table->foreign('installation_device_uuid')
                  ->references('uuid')
                  ->on('installation_devices')
                  ->onDelete('set null');

            // Index composite pour les lookups par unité installée triés par date
            // (remplacera à terme idx_alarm_events_device_triggered)
            $table->index(
                ['installation_device_uuid', 'triggered_at'],
                'idx_alarm_events_installation_device_triggered'
            );
        });
    }

    public function down(): void
    {
        Schema::table('alarm_events', function (Blueprint $table) {
            $table->dropIndex('idx_alarm_events_installation_device_triggered');
            $table->dropForeign(['installation_device_uuid']);
            $table->dropColumn('installation_device_uuid');
        });
    }
};

