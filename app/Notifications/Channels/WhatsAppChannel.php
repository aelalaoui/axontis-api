<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Canal WhatsApp personnalisé
 *
 * Utilise l'API Twilio WhatsApp Business.
 * Configurable via config/services.php
 */
class WhatsAppChannel
{
    /**
     * Envoyer la notification par WhatsApp
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // Vérifier si la notification a la méthode toWhatsApp
        if (!method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $message = $notification->toWhatsApp($notifiable);

        if (empty($message['to']) || empty($message['message'])) {
            Log::warning('WhatsAppChannel: Numéro ou message manquant', [
                'notifiable' => get_class($notifiable),
                'notification' => get_class($notification),
            ]);
            return;
        }

        try {
            $this->sendViaTwilio($message);

            Log::info('WhatsAppChannel: Message WhatsApp envoyé avec succès', [
                'to' => $this->maskPhoneNumber($message['to']),
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsAppChannel: Échec d\'envoi WhatsApp', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Envoyer via Twilio WhatsApp API
     */
    protected function sendViaTwilio(array $message): void
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.whatsapp_from', 'whatsapp:+14155238886');

        if (!$sid || !$token) {
            throw new \Exception('Configuration Twilio WhatsApp incomplète');
        }

        // Formatter le numéro WhatsApp
        $to = $this->formatWhatsAppNumber($message['to']);

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To' => $to,
                'Body' => $message['message'],
            ]);

        if ($response->failed()) {
            throw new \Exception('Twilio WhatsApp API error: ' . $response->body());
        }
    }

    /**
     * Formatter le numéro pour WhatsApp
     */
    protected function formatWhatsAppNumber(string $number): string
    {
        // Si le numéro commence déjà par "whatsapp:", le garder tel quel
        if (str_starts_with($number, 'whatsapp:')) {
            return $number;
        }

        // Nettoyer le numéro
        $cleaned = preg_replace('/[^0-9+]/', '', $number);

        // Ajouter le préfixe whatsapp:
        return 'whatsapp:' . $cleaned;
    }

    /**
     * Masquer le numéro de téléphone pour les logs
     */
    protected function maskPhoneNumber(string $phone): string
    {
        $phone = str_replace('whatsapp:', '', $phone);
        if (strlen($phone) > 6) {
            return substr($phone, 0, 3) . '***' . substr($phone, -3);
        }
        return '***';
    }
}
