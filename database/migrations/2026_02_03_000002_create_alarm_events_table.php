<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour la table alarm_events.
 *
 * Stocke TOUS les événements bruts reçus des centrales Hikvision.
 * Table volumineuse optimisée pour les insertions et consultations par date.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alarm_events', function (Blueprint $table) {
            // Primary key UUID
            $table->char('uuid', 36)->primary();

            // Foreign key vers la centrale d'alarme
            $table->char('alarm_device_uuid', 36)->nullable();
            $table->foreign('alarm_device_uuid')
                ->references('uuid')
                ->on('alarm_devices')
                ->onDelete('set null');

            // Foreign key vers l'alerte créée (si applicable)
            $table->char('alert_uuid', 36)->nullable();
            $table->foreign('alert_uuid')
                ->references('uuid')
                ->on('alerts')
                ->onDelete('set null');

            // Identification de la source (fallback si device inconnu)
            $table->string('source_ip', 45)->nullable()->comment('IP source du webhook');
            $table->string('source_mac', 17)->nullable()->comment('MAC de la centrale si fournie');

            // Type d'événement
            $table->string('event_type', 50)->index()->comment('Type: cidEvent, systemEvent, etc.');
            $table->unsignedSmallInteger('cid_code')->nullable()->index()->comment('Code CID original');
            $table->unsignedSmallInteger('standard_cid_code')->nullable()->index()->comment('Code CID standard');

            // Détails de l'événement
            $table->unsignedTinyInteger('zone_number')->nullable()->comment('Numéro de zone (1-64)');
            $table->unsignedTinyInteger('channel_id')->nullable()->comment('Channel ID');
            $table->string('event_state', 20)->default('active')->comment('active, inactive, restore');
            $table->string('event_description', 255)->nullable();

            // Classification (après traitement)
            $table->enum('alarm_type', ['intrusion', 'fire', 'flood', 'other', 'system'])->nullable();
            $table->enum('severity', ['low', 'medium', 'critical'])->nullable();

            // Payload brut
            $table->json('raw_payload')->comment('Payload JSON complet reçu');

            // Traitement
            $table->boolean('processed')->default(false)->index();
            $table->timestamp('processed_at')->nullable();
            $table->string('processing_error', 500)->nullable()->comment('Erreur de traitement si échec');

            // Déduplication
            $table->string('event_hash', 64)->nullable()->index()->comment('Hash pour déduplication');

            // Timestamps
            $table->timestamp('triggered_at')->index()->comment('Horodatage de l\'événement');
            $table->timestamps();

            // Index composites pour les requêtes fréquentes
            $table->index(['alarm_device_uuid', 'triggered_at']);
            $table->index(['alarm_device_uuid', 'cid_code', 'triggered_at']);
            $table->index(['processed', 'created_at']);
            $table->index(['alarm_type', 'triggered_at']);
            $table->index(['severity', 'processed']);

            // Index pour la recherche de doublons
            $table->index(['alarm_device_uuid', 'event_hash', 'triggered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alarm_events');
    }
};
