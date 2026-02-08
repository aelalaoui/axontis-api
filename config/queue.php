<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue API supports an assortment of back-ends via a single
    | API, giving you convenient access to each back-end using the same
    | syntax for every one. Here you may define a default connection.
    |
    */

    'default' => env('QUEUE_CONNECTION', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. A default configuration has been added
    | for each back-end shipped with Laravel. You are free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => 'localhost',
            'queue' => 'default',
            'retry_after' => 90,
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'queue',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Queues par Canal de Notification
    |--------------------------------------------------------------------------
    |
    | Configuration des queues dédiées pour chaque canal de communication.
    | Chaque canal a sa propre queue pour une gestion optimale et un
    | monitoring séparé.
    |
    | Commandes pour lancer les workers :
    |
    | # Worker dédié aux emails (priorité haute)
    | php artisan queue:work --queue=emails --tries=3 --timeout=60 --sleep=3
    |
    | # Worker dédié aux SMS
    | php artisan queue:work --queue=sms --tries=3 --timeout=30 --sleep=3
    |
    | # Worker dédié aux messageries (WhatsApp, Telegram)
    | php artisan queue:work --queue=whatsapp,telegram --tries=3 --timeout=30 --sleep=3
    |
    | # Worker pour toutes les notifications (dev)
    | php artisan queue:work --queue=emails,sms,whatsapp,telegram --tries=3
    |
    */

    'notification_queues' => [
        // Queue pour les emails (Resend/Mailgun/Brevo)
        'emails' => [
            'retry_after' => 90,
            'timeout' => 60,
            'tries' => 3,
            'backoff' => [10, 30, 60], // Retry après 10s, 30s, 60s
        ],

        // Queue pour les SMS (Twilio, Vonage, etc.)
        'sms' => [
            'retry_after' => 60,
            'timeout' => 30,
            'tries' => 3,
            'backoff' => [5, 15, 30],
        ],

        // Queue pour WhatsApp (Twilio WhatsApp, etc.)
        'whatsapp' => [
            'retry_after' => 60,
            'timeout' => 30,
            'tries' => 3,
            'backoff' => [5, 15, 30],
        ],

        // Queue pour Telegram et autres messageries
        'telegram' => [
            'retry_after' => 60,
            'timeout' => 30,
            'tries' => 3,
            'backoff' => [5, 15, 30],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    |
    | The following options configure the database and table that store job
    | batching information. These options can be updated to any database
    | connection and table which has been defined by your application.
    |
    */

    'batching' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

];
