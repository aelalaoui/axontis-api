<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Email Providers
    |--------------------------------------------------------------------------
    */

    // Resend - Provider principal (3000 emails/mois gratuits)
    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    // Mailgun - Backup 1 (5000 emails/mois gratuits pendant 3 mois)
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.eu.mailgun.net'),
        'scheme' => 'https',
    ],

    // Brevo (ex-Sendinblue) - Backup 2 (300 emails/jour gratuits)
    // Configuré dans mail.php via SMTP

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS & WhatsApp Providers
    |--------------------------------------------------------------------------
    */

    // Twilio pour SMS et WhatsApp
    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from' => env('TWILIO_FROM'), // Numéro SMS
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM', 'whatsapp:+14155238886'), // Numéro WhatsApp
    ],

    // Vonage (Nexmo) - Alternative pour SMS
    'vonage' => [
        'key' => env('VONAGE_KEY'),
        'secret' => env('VONAGE_SECRET'),
        'from' => env('VONAGE_FROM'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Messaging Providers
    |--------------------------------------------------------------------------
    */

    // Telegram Bot
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'), // Chat ID par défaut pour les alertes
    ],

    // Slack Webhooks
    'slack' => [
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
        'notifications_channel' => env('SLACK_NOTIFICATIONS_CHANNEL', '#notifications'),
        'alerts_channel' => env('SLACK_ALERTS_CHANNEL', '#alerts'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification System Configuration
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        // Équipe technique pour les alertes critiques
        'tech_team' => [
            'slack_webhook' => env('NOTIFICATION_SLACK_WEBHOOK'),
            'telegram_chat_id' => env('NOTIFICATION_TELEGRAM_CHAT_ID'),
            'email' => env('NOTIFICATION_TECH_EMAIL', 'tech@example.com'),
        ],

        // Rate limiting par canal (requêtes par minute)
        'rate_limits' => [
            'email' => env('NOTIFICATION_RATE_LIMIT_EMAIL', 60),
            'sms' => env('NOTIFICATION_RATE_LIMIT_SMS', 30),
            'whatsapp' => env('NOTIFICATION_RATE_LIMIT_WHATSAPP', 30),
            'telegram' => env('NOTIFICATION_RATE_LIMIT_TELEGRAM', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Other Services
    |--------------------------------------------------------------------------
    */

    'docusign' => [
        'base_path' => env('DOCUSIGN_BASE_PATH', 'https://demo.docusign.net/restapi'),
        'oauth_base_path' => env('DOCUSIGN_OAUTH_BASE_PATH', 'account-d.docusign.com'),
        'account_id' => env('DOCUSIGN_ACCOUNT_ID'),
        'client_id' => env('DOCUSIGN_CLIENT_ID'),
        'user_id' => env('DOCUSIGN_USER_ID'),
        'rsa_key_path' => storage_path('keys/docusign_key.key'),
        // HMAC key for webhook signature validation (from DocuSign Connect settings)
        'hmac_key' => env('DOCUSIGN_HMAC_KEY'),
    ],

    'stripe' => [
        'key' => env('STRIPE_PUBLIC_KEY'),
        'secret' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

];
