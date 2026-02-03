<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour la table alarm_devices.
 *
 * Stocke les informations spécifiques aux centrales d'alarme Hikvision AX PRO
 * connectées au système de télésurveillance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alarm_devices', function (Blueprint $table) {
            // Primary key UUID
            $table->char('uuid', 36)->primary();

            // Foreign key vers l'installation (lieu physique)
            $table->char('installation_uuid', 36)->nullable();
            $table->foreign('installation_uuid')
                ->references('uuid')
                ->on('installations')
                ->onDelete('set null');

            // Identification de la centrale
            $table->string('name', 100)->comment('Nom descriptif de la centrale');
            $table->string('serial_number', 50)->unique()->comment('Numéro de série Hikvision');
            $table->string('model', 50)->default('DS-PWA64-L-WB')->comment('Modèle de la centrale');

            // Configuration réseau
            $table->string('ip_address', 45)->nullable()->comment('Adresse IP (IPv4 ou IPv6)');
            $table->string('mac_address', 17)->nullable()->unique()->comment('Adresse MAC');
            $table->unsignedSmallInteger('port')->default(80)->comment('Port HTTP ISAPI');

            // Credentials API (chiffrés en application)
            $table->string('api_username', 100)->nullable()->comment('Username ISAPI');
            $table->text('api_password_encrypted')->nullable()->comment('Password ISAPI chiffré');

            // Statut et monitoring
            $table->enum('status', ['online', 'offline', 'error', 'configuring', 'unknown'])
                ->default('unknown')
                ->index()
                ->comment('Statut actuel de la centrale');
            $table->enum('arm_status', ['armed_away', 'armed_stay', 'disarmed', 'unknown'])
                ->default('unknown')
                ->comment('État d\'armement');
            $table->timestamp('last_heartbeat_at')->nullable()->comment('Dernier heartbeat reçu');
            $table->timestamp('last_event_at')->nullable()->comment('Dernier événement reçu');

            // Informations techniques
            $table->string('firmware_version', 50)->nullable();
            $table->unsignedTinyInteger('zone_count')->default(32)->comment('Nombre de zones configurées');
            $table->json('configuration')->nullable()->comment('Configuration JSON additionnelle');

            // Webhook configuration
            $table->boolean('webhook_enabled')->default(true)->comment('Webhook actif');
            $table->string('webhook_secret', 100)->nullable()->comment('Secret pour signature webhook');

            // Méta
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Index pour les recherches
            $table->index('ip_address');
            $table->index(['status', 'last_heartbeat_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alarm_devices');
    }
};
