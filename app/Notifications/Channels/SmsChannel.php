<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Canal SMS personnalisé
 *
 * Supporte Twilio et Vonage (Nexmo).
 * Configurable via config/services.php
 */
class SmsChannel
{
    /**
     * Envoyer la notification par SMS
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // Vérifier si la notification a la méthode toSms
        if (!method_exists($notification, 'toSms')) {
            return;
        }

        $message = $notification->toSms($notifiable);

        if (empty($message['to']) || empty($message['message'])) {
            Log::warning('SmsChannel: Numéro de téléphone ou message manquant', [
                'notifiable' => get_class($notifiable),
                'notification' => get_class($notification),
            ]);
            return;
        }

        $provider = config('services.sms.provider', 'twilio');

        try {
            match ($provider) {
                'twilio' => $this->sendViaTwilio($message),
                'vonage', 'nexmo' => $this->sendViaVonage($message),
                default => throw new \Exception("Provider SMS non supporté: {$provider}"),
            };

            Log::info('SmsChannel: SMS envoyé avec succès', [
                'to' => $this->maskPhoneNumber($message['to']),
                'provider' => $provider,
            ]);
        } catch (\Exception $e) {
            Log::error('SmsChannel: Échec d\'envoi SMS', [
                'error' => $e->getMessage(),
                'provider' => $provider,
            ]);
            throw $e;
        }
    }

    /**
     * Envoyer via Twilio
     */
    protected function sendViaTwilio(array $message): void
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');

        if (!$sid || !$token || !$from) {
            throw new \Exception('Configuration Twilio incomplète');
        }

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To' => $message['to'],
                'Body' => $message['message'],
            ]);

        if ($response->failed()) {
            throw new \Exception('Twilio API error: ' . $response->body());
        }
    }

    /**
     * Envoyer via Vonage (Nexmo)
     */
    protected function sendViaVonage(array $message): void
    {
        $key = config('services.vonage.key');
        $secret = config('services.vonage.secret');
        $from = config('services.vonage.from');

        if (!$key || !$secret || !$from) {
            throw new \Exception('Configuration Vonage incomplète');
        }

        $response = Http::post('https://rest.nexmo.com/sms/json', [
            'api_key' => $key,
            'api_secret' => $secret,
            'from' => $from,
            'to' => $message['to'],
            'text' => $message['message'],
        ]);

        if ($response->failed()) {
            throw new \Exception('Vonage API error: ' . $response->body());
        }

        $result = $response->json();
        if (isset($result['messages'][0]['status']) && $result['messages'][0]['status'] !== '0') {
            throw new \Exception('Vonage error: ' . ($result['messages'][0]['error-text'] ?? 'Unknown error'));
        }
    }

    /**
     * Masquer le numéro de téléphone pour les logs
     */
    protected function maskPhoneNumber(string $phone): string
    {
        if (strlen($phone) > 6) {
            return substr($phone, 0, 3) . '***' . substr($phone, -3);
        }
        return '***';
    }
}
