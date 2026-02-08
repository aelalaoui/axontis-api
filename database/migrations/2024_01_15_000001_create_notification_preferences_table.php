<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour la table notification_preferences
 *
 * Stocke les préférences de notification pour chaque utilisateur ou client.
 * Permet de définir quels canaux activer et les informations de contact.
 */
return new class extends Migration
{
    /**
     * Exécuter la migration
     */
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique()->nullable();

            // Relation polymorphique (User ou Client)
            $table->morphs('notifiable');

            // Canaux activés
            $table->boolean('notify_email')->default(true)->comment('Email activé');
            $table->boolean('notify_sms')->default(false)->comment('SMS activé');
            $table->boolean('notify_whatsapp')->default(false)->comment('WhatsApp activé');
            $table->boolean('notify_telegram')->default(false)->comment('Telegram activé');
            $table->boolean('notify_slack')->default(false)->comment('Slack activé');

            // Informations de contact par canal
            $table->string('phone_number', 20)->nullable()->comment('Numéro pour SMS');
            $table->string('whatsapp_number', 20)->nullable()->comment('Numéro WhatsApp');
            $table->string('telegram_chat_id', 50)->nullable()->comment('Chat ID Telegram');
            $table->string('slack_webhook_url', 500)->nullable()->comment('Webhook Slack personnalisé');

            // Types de notifications activés (optionnel, pour filtrage fin)
            $table->json('notification_types')->nullable()->comment('Types de notifications activés');

            $table->timestamps();

            // Index pour optimiser les requêtes
            // Note: morphs() crée déjà l'index sur notifiable_type et notifiable_id
            $table->index('notify_email');
            $table->index('notify_sms');
        });
    }

    /**
     * Annuler la migration
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
