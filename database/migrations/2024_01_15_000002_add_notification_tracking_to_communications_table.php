<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour ajouter les colonnes de traçabilité à la table communications
 *
 * Ajoute les colonnes status, notification_type, provider, metadata, retry_count, failed_at
 * pour le suivi complet des notifications envoyées.
 */
return new class extends Migration
{
    /**
     * Exécuter la migration
     */
    public function up(): void
    {
        Schema::table('communications', function (Blueprint $table) {
            // Statut de la communication
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed'])
                ->default('pending')
                ->after('sent_at')
                ->comment('Statut de la communication');

            // Classe de notification utilisée
            $table->string('notification_type', 255)
                ->nullable()
                ->after('status')
                ->comment('Classe de notification utilisée');

            // Provider utilisé pour l\'envoi
            $table->string('provider', 50)
                ->nullable()
                ->after('notification_type')
                ->comment('Provider utilisé (resend, mailgun, brevo, twilio)');

            // Métadonnées supplémentaires (erreurs, tracking, etc.)
            $table->json('metadata')
                ->nullable()
                ->after('provider')
                ->comment('Données supplémentaires (erreurs, tracking, etc.)');

            // Compteur de tentatives
            $table->unsignedTinyInteger('retry_count')
                ->default(0)
                ->after('metadata')
                ->comment('Nombre de tentatives');

            // Date d\'échec définitif
            $table->timestamp('failed_at')
                ->nullable()
                ->after('retry_count')
                ->comment('Date d\'échec définitif');

            // Index pour optimiser les requêtes
            $table->index('status');
            $table->index('notification_type');
            $table->index('provider');
            $table->index('failed_at');
        });
    }

    /**
     * Annuler la migration
     */
    public function down(): void
    {
        Schema::table('communications', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['notification_type']);
            $table->dropIndex(['provider']);
            $table->dropIndex(['failed_at']);

            $table->dropColumn([
                'status',
                'notification_type',
                'provider',
                'metadata',
                'retry_count',
                'failed_at',
            ]);
        });
    }
};
