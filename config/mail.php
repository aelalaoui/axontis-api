<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send any email
    | messages sent by your application. Alternative mailers may be setup
    | and used as needed; however, this mailer will be used by default.
    |
    | Pour la production, utiliser 'failover' pour activer le système
    | de repli automatique : Resend → SMTP
    |
    */

    'default' => env('MAIL_MAILER', 'failover'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers to be used while
    | sending an e-mail. You will specify which one you are using for your
    | mailers below. You are free to add additional mailers as required.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array", "failover", "roundrobin"
    |
    */

    'mailers' => [

        /*
        |--------------------------------------------------------------------------
        | Resend - Provider Principal (3000 emails/mois gratuits)
        |--------------------------------------------------------------------------
        */
        'resend' => [
            'transport' => 'resend',
            'from' => [
                'address' => 'noreply@mail.axontis.net',
                'name' => "Axontis",
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Mailgun - Backup 1 (5000 emails/mois gratuits pendant 3 mois)
        |--------------------------------------------------------------------------
        */
        'mailgun' => [
            'transport' => 'mailgun',
            'client' => [
                'timeout' => 10,
            ],
            'from' => [
                'address' => 'noreply@mailgun.axontis.net',
                'name' => "Axontis",
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Brevo (ex-Sendinblue) - Backup 2 (300 emails/jour gratuits)
        |--------------------------------------------------------------------------
        */
        'brevo' => [
            'transport' => 'brevo',
            'key' => env('BREVO_API_KEY'),
            'from' => [
                'address' => 'noreply@email.axontis.net',
                'name' => "Axontis",
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Failover - Cascade automatique : Resend → SMTP
        |--------------------------------------------------------------------------
        | Si Resend échoue, Laravel essaiera automatiquement SMTP local
        | comme dernier recours. Cela garantit qu'au moins un mailer
        | fonctionne toujours.
        |
        | Note: Pour activer Mailgun ou Brevo, installez d'abord les packages:
        | - Mailgun: composer require symfony/mailgun-mailer
        | - Brevo: déjà supporté via SMTP
        */
        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'brevo',     // starting integration
                'resend',    // Principal (package installé)
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | SMTP Générique
        |--------------------------------------------------------------------------
        */
        'smtp' => [
            'transport' => 'smtp',
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ],

        'ses' => [
            'transport' => 'ses',
            'from' => [
                'address' => 'noreply@ses.axontis.net',
                'name' => "Axontis",
            ],
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => null,
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all e-mails sent by your application to be sent from
    | the same address. Here, you may specify a name and address that is
    | used globally for all e-mails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@axontis.net'),
        'name' => env('MAIL_FROM_NAME', 'Axontis'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Markdown Mail Settings
    |--------------------------------------------------------------------------
    |
    | If you are using Markdown based email rendering, you may configure your
    | theme and component paths here, allowing you to customize the design
    | of the emails. Or, you may simply stick with the Laravel defaults!
    |
    */

    'markdown' => [
        'theme' => 'default',

        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],

];
