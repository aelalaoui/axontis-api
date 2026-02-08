<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        });

        // Créer les index après avoir ajouté les colonnes
        // Note: Limitation de la longueur des index pour éviter l'erreur MySQL "key too long"
        Schema::table('communications', function (Blueprint $table) {
            $table->index('status');
            $table->index([DB::raw('notification_type(100)')], 'communications_notification_type_index');
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
            // Supprimer les index
            $table->dropIndex(['status']);
            $table->dropIndex('communications_notification_type_index');
            $table->dropIndex(['provider']);
            $table->dropIndex(['failed_at']);

            // Supprimer les colonnes
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
