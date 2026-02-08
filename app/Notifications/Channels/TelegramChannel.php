<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Canal Telegram personnalisé
 *
 * Utilise l'API Telegram Bot.
 * Configurable via config/services.php
 */
class TelegramChannel
{
    /**
     * URL de base de l'API Telegram
     */
    protected string $baseUrl = 'https://api.telegram.org/bot';

    /**
     * Envoyer la notification par Telegram
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // Vérifier si la notification a la méthode toTelegram
        if (!method_exists($notification, 'toTelegram')) {
            return;
        }

        $message = $notification->toTelegram($notifiable);

        if (empty($message['chat_id']) || empty($message['text'])) {
            Log::warning('TelegramChannel: Chat ID ou message manquant', [
                'notifiable' => get_class($notifiable),
                'notification' => get_class($notification),
            ]);
            return;
        }

        try {
            $this->sendMessage($message);

            Log::info('TelegramChannel: Message Telegram envoyé avec succès', [
                'chat_id' => $message['chat_id'],
            ]);
        } catch (\Exception $e) {
            Log::error('TelegramChannel: Échec d\'envoi Telegram', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Envoyer un message via l'API Telegram
     */
    protected function sendMessage(array $message): void
    {
        $token = config('services.telegram.bot_token');

        if (!$token) {
            throw new \Exception('Token Telegram Bot non configuré');
        }

        $payload = [
            'chat_id' => $message['chat_id'],
            'text' => $message['text'],
            'parse_mode' => $message['parse_mode'] ?? 'Markdown',
            'disable_web_page_preview' => $message['disable_preview'] ?? false,
        ];

        // Ajouter le clavier inline si présent
        if (isset($message['reply_markup'])) {
            $payload['reply_markup'] = json_encode($message['reply_markup']);
        }

        $response = Http::post("{$this->baseUrl}{$token}/sendMessage", $payload);

        if ($response->failed()) {
            $error = $response->json()['description'] ?? $response->body();
            throw new \Exception('Telegram API error: ' . $error);
        }

        $result = $response->json();
        if (!isset($result['ok']) || !$result['ok']) {
            throw new \Exception('Telegram error: ' . ($result['description'] ?? 'Unknown error'));
        }
    }

    /**
     * Envoyer une photo avec légende
     */
    public function sendPhoto(array $message): void
    {
        $token = config('services.telegram.bot_token');

        if (!$token) {
            throw new \Exception('Token Telegram Bot non configuré');
        }

        $payload = [
            'chat_id' => $message['chat_id'],
            'photo' => $message['photo'],
            'caption' => $message['caption'] ?? '',
            'parse_mode' => $message['parse_mode'] ?? 'Markdown',
        ];

        $response = Http::post("{$this->baseUrl}{$token}/sendPhoto", $payload);

        if ($response->failed()) {
            throw new \Exception('Telegram API error: ' . $response->body());
        }
    }

    /**
     * Envoyer un document
     */
    public function sendDocument(array $message): void
    {
        $token = config('services.telegram.bot_token');

        if (!$token) {
            throw new \Exception('Token Telegram Bot non configuré');
        }

        $payload = [
            'chat_id' => $message['chat_id'],
            'document' => $message['document'],
            'caption' => $message['caption'] ?? '',
            'parse_mode' => $message['parse_mode'] ?? 'Markdown',
        ];

        $response = Http::post("{$this->baseUrl}{$token}/sendDocument", $payload);

        if ($response->failed()) {
            throw new \Exception('Telegram API error: ' . $response->body());
        }
    }
}
