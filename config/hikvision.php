<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hik-Partner Pro OpenAPI Credentials
    |--------------------------------------------------------------------------
    |
    | ARC credentials for Hik-Partner Pro cloud integration.
    | NEVER commit these values — use .env only.
    |
    */

    'hpp' => [
        'arc_id' => env('HPP_ARC_ID'),
        'arc_key' => env('HPP_ARC_KEY'),
        'base_url' => env('HPP_API_BASE_URL', 'https://eu.hik-partner.com'),
        'connect_timeout' => env('HPP_CONNECT_TIMEOUT', 5),
        'request_timeout' => env('HPP_REQUEST_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    */

    'webhook' => [
        'deduplicate_window' => env('HIKVISION_DEDUPLICATE_WINDOW', 60),
        'rate_limit_per_minute' => env('HIKVISION_RATE_LIMIT', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Retention
    |--------------------------------------------------------------------------
    */

    'events' => [
        'retention_days' => env('HIKVISION_EVENT_RETENTION_DAYS', 365),
        'queue' => env('ALARM_EVENTS_QUEUE', 'alarm-events'),
    ],

    /*
    |--------------------------------------------------------------------------
    | CID Code Mapping
    |--------------------------------------------------------------------------
    |
    | Maps Contact ID codes from AX PRO to categories and severities.
    | Format: code => [category, severity, description]
    |
    */

    'cid_mapping' => [
        // Intrusion — critical
        1759 => ['category' => 'intrusion', 'severity' => 'critical', 'description' => 'Alarme zone / intrusion'],
        3130 => ['category' => 'intrusion', 'severity' => 'critical', 'description' => 'Alarme zone / intrusion (CID standard)'],

        // Fire — critical
        110 => ['category' => 'fire', 'severity' => 'critical', 'description' => 'Alarme incendie'],
        111 => ['category' => 'fire', 'severity' => 'critical', 'description' => 'Détection fumée'],
        114 => ['category' => 'fire', 'severity' => 'critical', 'description' => 'Détection chaleur'],

        // Panic — critical
        120 => ['category' => 'panic', 'severity' => 'critical', 'description' => 'Panique sonore'],
        121 => ['category' => 'panic', 'severity' => 'critical', 'description' => 'Contrainte (duress)'],
        122 => ['category' => 'panic', 'severity' => 'critical', 'description' => 'Panique silencieuse'],

        // Flood — high
        154 => ['category' => 'flood', 'severity' => 'high', 'description' => 'Détection inondation'],

        // System — high
        305 => ['category' => 'system', 'severity' => 'high', 'description' => 'Sabotage détecteur'],

        // Arming — info (state changes, no alert)
        3401 => ['category' => 'arming', 'severity' => 'info', 'description' => 'Armement total (Away)', 'arm_status' => 'armed_away'],
        3441 => ['category' => 'arming', 'severity' => 'info', 'description' => 'Armement partiel (Stay)', 'arm_status' => 'armed_stay'],
        1401 => ['category' => 'arming', 'severity' => 'info', 'description' => 'Désarmement', 'arm_status' => 'disarmed'],

        // Power — medium/info
        602 => ['category' => 'system', 'severity' => 'medium', 'description' => 'Perte secteur'],
        603 => ['category' => 'system', 'severity' => 'info', 'description' => 'Retour secteur'],
    ],

];

