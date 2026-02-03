<?php

/**
 * Hikvision AX PRO Integration Configuration
 *
 * Configuration pour l'intégration des centrales d'alarme Hikvision DS-PWA64-L-WB
 * supportant ISAPI/HTTP et les événements CID (Contact ID).
 */

return [

    /*
    |--------------------------------------------------------------------------
    | ISAPI Authentication
    |--------------------------------------------------------------------------
    |
    | Credentials par défaut pour l'authentification HTTP Digest aux centrales.
    | Ces valeurs peuvent être surchargées par centrale dans la table alarm_devices.
    |
    */

    'default_username' => env('HIKVISION_DEFAULT_USERNAME', 'admin'),
    'default_password' => env('HIKVISION_DEFAULT_PASSWORD', ''),
    'default_port' => env('HIKVISION_DEFAULT_PORT', 80),

    /*
    |--------------------------------------------------------------------------
    | Webhook Security
    |--------------------------------------------------------------------------
    |
    | Configuration de sécurité pour les webhooks entrants.
    | ip_whitelist: Liste des IPs autorisées à envoyer des événements.
    | Laisser vide pour désactiver le filtrage IP (non recommandé en production).
    |
    */

    'webhook' => [
        'ip_whitelist' => array_filter(explode(',', env('HIKVISION_IP_WHITELIST', ''))),
        'signature_header' => env('HIKVISION_SIGNATURE_HEADER', 'X-Hikvision-Signature'),
        'signature_secret' => env('HIKVISION_SIGNATURE_SECRET', ''),
        'rate_limit' => env('HIKVISION_WEBHOOK_RATE_LIMIT', 1000), // requêtes par minute
    ],

    /*
    |--------------------------------------------------------------------------
    | API Timeouts
    |--------------------------------------------------------------------------
    |
    | Timeouts pour les appels HTTP vers les centrales Hikvision.
    |
    */

    'timeouts' => [
        'connect' => env('HIKVISION_CONNECT_TIMEOUT', 5),
        'request' => env('HIKVISION_REQUEST_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Polling Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour le polling ISAPI en fallback.
    |
    */

    'polling' => [
        'enabled' => env('HIKVISION_POLLING_ENABLED', false),
        'interval' => env('HIKVISION_POLLING_INTERVAL', 30), // secondes
        'batch_size' => env('HIKVISION_POLLING_BATCH_SIZE', 100), // centrales par batch
    ],

    /*
    |--------------------------------------------------------------------------
    | Heartbeat Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour le check de statut des centrales.
    |
    */

    'heartbeat' => [
        'enabled' => env('HIKVISION_HEARTBEAT_ENABLED', true),
        'interval' => env('HIKVISION_HEARTBEAT_INTERVAL', 300), // 5 minutes
        'offline_threshold' => env('HIKVISION_OFFLINE_THRESHOLD', 600), // 10 minutes sans heartbeat
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Processing
    |--------------------------------------------------------------------------
    |
    | Configuration pour le traitement des événements.
    |
    */

    'events' => [
        'queue' => env('HIKVISION_EVENTS_QUEUE', 'alarm-events'),
        'deduplicate_window' => env('HIKVISION_DEDUPLICATE_WINDOW', 60), // secondes
        'retention_days' => env('HIKVISION_EVENT_RETENTION_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | CID Code Mapping
    |--------------------------------------------------------------------------
    |
    | Mapping des codes CID (Contact ID) vers les types d'alertes.
    | null = événement système, ne crée pas d'alerte.
    |
    */

    'cid_mapping' => [
        // Intrusions - Critical
        1759 => ['type' => 'intrusion', 'severity' => 'critical', 'description' => 'Alarme intrusion'],
        3130 => ['type' => 'intrusion', 'severity' => 'critical', 'description' => 'Alarme de zone'],
        130 => ['type' => 'intrusion', 'severity' => 'critical', 'description' => 'Effraction périmétrique'],
        131 => ['type' => 'intrusion', 'severity' => 'critical', 'description' => 'Effraction périmétrique'],
        132 => ['type' => 'intrusion', 'severity' => 'critical', 'description' => 'Détection mouvement intérieur'],
        133 => ['type' => 'intrusion', 'severity' => 'critical', 'description' => 'Détection mouvement intérieur'],
        134 => ['type' => 'intrusion', 'severity' => 'medium', 'description' => 'Entrée/Sortie'],
        136 => ['type' => 'intrusion', 'severity' => 'critical', 'description' => 'Alarme extérieure'],

        // Incendie - Critical
        110 => ['type' => 'fire', 'severity' => 'critical', 'description' => 'Alarme incendie'],
        111 => ['type' => 'fire', 'severity' => 'critical', 'description' => 'Détection fumée'],
        112 => ['type' => 'fire', 'severity' => 'critical', 'description' => 'Détection combustion'],
        113 => ['type' => 'fire', 'severity' => 'critical', 'description' => 'Alarme eau'],
        114 => ['type' => 'fire', 'severity' => 'critical', 'description' => 'Détection chaleur'],
        115 => ['type' => 'fire', 'severity' => 'critical', 'description' => 'Détection de montée en température'],
        116 => ['type' => 'fire', 'severity' => 'critical', 'description' => 'Conduit incendie'],
        117 => ['type' => 'fire', 'severity' => 'critical', 'description' => 'Alarme flamme'],
        118 => ['type' => 'fire', 'severity' => 'critical', 'description' => 'Proximité alarme'],

        // Inondation - Critical
        154 => ['type' => 'flood', 'severity' => 'critical', 'description' => 'Détection inondation/fuite eau'],

        // Panique - Critical
        120 => ['type' => 'other', 'severity' => 'critical', 'description' => 'Alarme panique sonore'],
        121 => ['type' => 'other', 'severity' => 'critical', 'description' => 'Alarme contrainte (duress)'],
        122 => ['type' => 'other', 'severity' => 'critical', 'description' => 'Alarme panique silencieuse'],
        123 => ['type' => 'other', 'severity' => 'critical', 'description' => 'Alarme panique auxiliaire'],

        // Sabotage - Medium to Critical
        137 => ['type' => 'other', 'severity' => 'medium', 'description' => 'Sabotage zone'],
        145 => ['type' => 'other', 'severity' => 'medium', 'description' => 'Sabotage système'],
        341 => ['type' => 'other', 'severity' => 'medium', 'description' => 'Perte communication'],
        344 => ['type' => 'other', 'severity' => 'medium', 'description' => 'Interférence RF'],

        // Événements système - Ne créent PAS d'alerte
        3401 => null, // Armement total (Arm Away)
        3441 => null, // Armement partiel (Arm Stay)
        1401 => null, // Désarmement
        3456 => null, // Armement partiel instant
        3407 => null, // Armement avec bypass
        401 => null,  // Armement/Désarmement utilisateur
        402 => null,  // Armement partiel utilisateur
        403 => null,  // Armement automatique
        407 => null,  // Armement à distance
        408 => null,  // Armement rapide
        409 => null,  // Armement par téléphone
        411 => null,  // Rappel de callback demandé
        412 => null,  // Téléchargement/Upload réussi
        570 => null,  // Bypass zone
        574 => null,  // Bypass groupe

        // Maintenance - Low
        300 => ['type' => 'other', 'severity' => 'low', 'description' => 'Batterie système faible'],
        301 => ['type' => 'other', 'severity' => 'low', 'description' => 'Batterie système vide'],
        302 => ['type' => 'other', 'severity' => 'low', 'description' => 'Panne secteur'],
        305 => ['type' => 'other', 'severity' => 'low', 'description' => 'Reset système'],
        306 => ['type' => 'other', 'severity' => 'low', 'description' => 'Programmation modifiée'],
        309 => ['type' => 'other', 'severity' => 'low', 'description' => 'Batterie zone faible'],
        311 => ['type' => 'other', 'severity' => 'low', 'description' => 'Batterie zone vide'],
        380 => ['type' => 'other', 'severity' => 'low', 'description' => 'Supervision zone'],
        381 => ['type' => 'other', 'severity' => 'low', 'description' => 'Supervision module'],
        384 => ['type' => 'other', 'severity' => 'low', 'description' => 'Batterie répéteur faible'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Models
    |--------------------------------------------------------------------------
    |
    | Modèles de centrales Hikvision supportés.
    |
    */

    'supported_models' => [
        'DS-PWA64-L-WB',  // AX PRO - Wireless Control Panel
        'DS-PWA96-M-WE',  // AX PRO - 96 zones
        'DS-PWA32-HSG',   // AX Hybrid - 32 zones
        'DS-PWA64-L-WE',  // AX PRO Europe
        'DS-PHA64-L',     // Hybrid panel
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configuration de logging pour le debugging.
    |
    */

    'logging' => [
        'enabled' => env('HIKVISION_LOGGING_ENABLED', true),
        'channel' => env('HIKVISION_LOG_CHANNEL', 'stack'),
        'level' => env('HIKVISION_LOG_LEVEL', 'info'),
    ],

];
