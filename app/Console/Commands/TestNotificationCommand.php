<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\User;
use App\Notifications\InvoiceNotification;
use App\Notifications\OrderConfirmationNotification;
use App\Notifications\SmsVerificationNotification;
use App\Notifications\UrgentAlertNotification;
use App\Notifications\WelcomeNotification;
use App\Notifications\WhatsAppOrderUpdateNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Commande pour tester les notifications
 *
 * Usage :
 *   php artisan notification:test welcome {clientId}
 *   php artisan notification:test invoice {clientId}
 *   php artisan notification:test order {clientId}
 *   php artisan notification:test sms {clientId}
 *   php artisan notification:test whatsapp {clientId}
 *   php artisan notification:test alert {userId}
 *   php artisan notification:test failover
 */
class TestNotificationCommand extends Command
{
    /**
     * Signature de la commande
     */
    protected $signature = 'notification:test
                            {type : Type de notification (welcome, invoice, order, sms, whatsapp, alert, failover)}
                            {id? : ID du client ou utilisateur (optionnel pour failover)}
                            {--sync : Envoyer en synchrone (sans queue)}
                            {--email= : Email de test (override le destinataire)}';

    /**
     * Description de la commande
     */
    protected $description = 'Tester l\'envoi des différentes notifications';

    /**
     * Exécuter la commande
     */
    public function handle(): int
    {
        $type = $this->argument('type');
        $id = $this->argument('id');

        $this->info("🔔 Test de notification : {$type}");
        $this->newLine();

        try {
            match ($type) {
                'welcome' => $this->testWelcome($id),
                'invoice' => $this->testInvoice($id),
                'order' => $this->testOrderConfirmation($id),
                'sms' => $this->testSms($id),
                'whatsapp' => $this->testWhatsApp($id),
                'alert' => $this->testUrgentAlert($id),
                'failover' => $this->testFailover(),
                default => $this->error("Type de notification inconnu : {$type}"),
            };

            $this->newLine();
            $this->info('✅ Notification envoyée avec succès !');

            if (!$this->option('sync')) {
                $this->warn('📬 La notification est en queue. Lancez le worker pour la traiter :');
                $this->line('   php artisan queue:work --queue=emails,sms,whatsapp,telegram');
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Erreur : ' . $e->getMessage());
            $this->line($e->getTraceAsString());
            return self::FAILURE;
        }
    }

    /**
     * Tester la notification de bienvenue
     */
    protected function testWelcome(?string $id): void
    {
        $notifiable = $this->getNotifiable($id, 'client');

        $notification = new WelcomeNotification(
            userName: $notifiable->full_name ?? $notifiable->name ?? 'Utilisateur Test',
            activationLink: route('user.setup-password') . '?token=test-token-123',
            companyName: config('app.name')
        );

        $this->sendNotification($notifiable, $notification);

        $this->table(
            ['Paramètre', 'Valeur'],
            [
                ['Destinataire', $notifiable->email],
                ['Type', 'WelcomeNotification'],
                ['Queue', 'emails'],
                ['Canal', 'Email'],
            ]
        );
    }

    /**
     * Tester la notification de facture
     */
    protected function testInvoice(?string $id): void
    {
        $notifiable = $this->getNotifiable($id, 'client');

        $notification = new InvoiceNotification(
            invoiceNumber: 'FAC-2024-00123',
            invoiceDate: now()->format('d/m/Y'),
            amountDue: 1250.00,
            dueDate: now()->addDays(30)->format('d/m/Y'),
            pdfUrl: url('/invoices/FAC-2024-00123.pdf'),
            clientName: $notifiable->full_name ?? $notifiable->name ?? 'Client Test'
        );

        $this->sendNotification($notifiable, $notification);

        $this->table(
            ['Paramètre', 'Valeur'],
            [
                ['Destinataire', $notifiable->email],
                ['Type', 'InvoiceNotification'],
                ['Queues', 'emails, sms'],
                ['Canaux', 'Email + SMS (si activé)'],
            ]
        );
    }

    /**
     * Tester la notification de confirmation de commande
     */
    protected function testOrderConfirmation(?string $id): void
    {
        $notifiable = $this->getNotifiable($id, 'client');

        $notification = new OrderConfirmationNotification(
            orderNumber: 'CMD-2024-00456',
            orderDate: now()->format('d/m/Y H:i'),
            totalAmount: 599.99,
            items: [
                ['name' => 'Produit A', 'quantity' => 2, 'price' => 199.99],
                ['name' => 'Produit B', 'quantity' => 1, 'price' => 199.99],
            ],
            trackingLink: url('/orders/CMD-2024-00456/track'),
            customerName: $notifiable->full_name ?? $notifiable->name ?? 'Client Test'
        );

        $this->sendNotification($notifiable, $notification);

        $this->table(
            ['Paramètre', 'Valeur'],
            [
                ['Destinataire', $notifiable->email],
                ['Type', 'OrderConfirmationNotification'],
                ['Queues', 'emails, telegram'],
                ['Canaux', 'Email + Telegram (si activé)'],
            ]
        );
    }

    /**
     * Tester la notification SMS
     */
    protected function testSms(?string $id): void
    {
        $notifiable = $this->getNotifiable($id, 'client');

        $notification = new SmsVerificationNotification(
            code: '123456',
            expiresInMinutes: 10,
            userName: $notifiable->full_name ?? $notifiable->name ?? 'Utilisateur'
        );

        $this->sendNotification($notifiable, $notification);

        $this->table(
            ['Paramètre', 'Valeur'],
            [
                ['Destinataire', $notifiable->phone ?? 'N/A'],
                ['Type', 'SmsVerificationNotification'],
                ['Queue', 'sms'],
                ['Canal', 'SMS uniquement'],
            ]
        );

        if (!$notifiable->phone) {
            $this->warn('⚠️  Le client n\'a pas de numéro de téléphone configuré.');
        }
    }

    /**
     * Tester la notification WhatsApp
     */
    protected function testWhatsApp(?string $id): void
    {
        $notifiable = $this->getNotifiable($id, 'client');

        $notification = new WhatsAppOrderUpdateNotification(
            orderNumber: 'CMD-2024-00789',
            status: 'in_delivery',
            trackingUrl: url('/orders/CMD-2024-00789/track')
        );

        $this->sendNotification($notifiable, $notification);

        $this->table(
            ['Paramètre', 'Valeur'],
            [
                ['Destinataire', $notifiable->phone ?? 'N/A'],
                ['Type', 'WhatsAppOrderUpdateNotification'],
                ['Queue', 'whatsapp'],
                ['Canal', 'WhatsApp uniquement'],
            ]
        );

        if (!$notifiable->phone) {
            $this->warn('⚠️  Le client n\'a pas de numéro WhatsApp configuré.');
        }
    }

    /**
     * Tester l'alerte urgente
     */
    protected function testUrgentAlert(?string $id): void
    {
        $notifiable = $this->getNotifiable($id, 'user');

        $notification = new UrgentAlertNotification(
            alertTitle: 'Test d\'alerte urgente',
            alertMessage: 'Ceci est un test du système d\'alerte. Si vous recevez ce message, le système fonctionne correctement.',
            severity: 'warning',
            actionUrl: url('/admin/alerts')
        );

        $this->sendNotification($notifiable, $notification);

        $this->table(
            ['Paramètre', 'Valeur'],
            [
                ['Destinataire', $notifiable->email],
                ['Type', 'UrgentAlertNotification'],
                ['Queues', 'emails, telegram'],
                ['Canaux', 'Email + Slack + Telegram'],
                ['Sévérité', 'warning'],
            ]
        );
    }

    /**
     * Tester le failover email
     */
    protected function testFailover(): void
    {
        $this->info('🔄 Test du failover email (Resend → Mailgun → Brevo)');
        $this->newLine();

        $this->warn('Ce test va vérifier la configuration du failover.');
        $this->line('Le failover est géré automatiquement par Laravel via le transport "failover".');
        $this->newLine();

        // Vérifier la configuration
        $defaultMailer = config('mail.default');
        $this->table(
            ['Configuration', 'Valeur'],
            [
                ['Mailer par défaut', $defaultMailer],
                ['Resend API Key', config('services.resend.key') ? '✅ Configuré' : '❌ Non configuré'],
                ['Mailgun Domain', config('services.mailgun.domain') ? '✅ Configuré' : '❌ Non configuré'],
                ['Brevo SMTP', config('mail.mailers.brevo.host') ? '✅ Configuré' : '❌ Non configuré'],
            ]
        );

        if ($defaultMailer === 'failover') {
            $this->info('✅ Le failover est activé !');
            $mailers = config('mail.mailers.failover.mailers', []);
            $this->line('   Ordre des providers : ' . implode(' → ', $mailers));
        } else {
            $this->warn('⚠️  Le failover n\'est pas activé. Mailer actuel : ' . $defaultMailer);
            $this->line('   Pour activer le failover, définissez MAIL_MAILER=failover dans .env');
        }
    }

    /**
     * Obtenir le notifiable (client ou user)
     */
    protected function getNotifiable(?string $id, string $type): Client|User
    {
        if ($id) {
            if ($type === 'client') {
                return Client::findOrFail($id);
            }
            return User::findOrFail($id);
        }

        // Chercher un notifiable par défaut
        if ($type === 'client') {
            $notifiable = Client::first();
            if (!$notifiable) {
                throw new \RuntimeException('Aucun client trouvé. Créez un client ou spécifiez un ID.');
            }
        } else {
            $notifiable = User::first();
            if (!$notifiable) {
                throw new \RuntimeException('Aucun utilisateur trouvé. Créez un utilisateur ou spécifiez un ID.');
            }
        }

        return $notifiable;
    }

    /**
     * Envoyer la notification
     */
    protected function sendNotification(Client|User $notifiable, $notification): void
    {
        // Override email si spécifié
        if ($email = $this->option('email')) {
            $notifiable->email = $email;
        }

        if ($this->option('sync')) {
            // Envoi synchrone
            Notification::sendNow($notifiable, $notification);
        } else {
            // Envoi via queue
            $notifiable->notify($notification);
        }
    }
}
