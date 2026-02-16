<?php

namespace Tests\Feature\Notifications;

use App\Models\Client;
use App\Models\Communication;
use App\Models\User;
use App\Notifications\InvoiceNotification;
use App\Notifications\OrderConfirmationNotification;
use App\Notifications\SmsVerificationNotification;
use App\Notifications\UrgentAlertNotification;
use App\Notifications\WelcomeNotification;
use App\Notifications\WhatsAppOrderUpdateNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Tests du système de notifications
 */
class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected Client $client;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@test.com',
        ]);

        $this->client = Client::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'Client',
            'email' => 'client@test.com',
            'phone' => '+33612345678',
        ]);
    }

    // =========================================
    // Tests des notifications individuelles
    // =========================================

    /** @test */
    public function welcome_notification_is_sent_to_mail_channel(): void
    {
        Notification::fake();

        $notification = new WelcomeNotification(
            userName: 'Test User',
            activationLink: 'https://example.com/activate',
            companyName: 'Test Company'
        );

        $this->client->notify($notification);

        Notification::assertSentTo(
            $this->client,
            WelcomeNotification::class,
            function ($notification, $channels) {
                return in_array('mail', $channels);
            }
        );
    }

    /** @test */
    public function welcome_notification_has_correct_subject(): void
    {
        $notification = new WelcomeNotification(
            userName: 'Test User',
            activationLink: 'https://example.com/activate',
            companyName: 'My Company'
        );

        $this->assertStringContainsString('My Company', $notification->subject);
    }

    /** @test */
    public function invoice_notification_uses_correct_channels(): void
    {
        Notification::fake();

        $notification = new InvoiceNotification(
            invoiceNumber: 'FAC-001',
            invoiceDate: '01/01/2024',
            amountDue: 100.00,
            dueDate: '31/01/2024',
            pdfUrl: 'https://example.com/invoice.pdf',
            clientName: 'Test Client'
        );

        $this->client->notify($notification);

        Notification::assertSentTo($this->client, InvoiceNotification::class);
    }

    /** @test */
    public function order_confirmation_notification_has_items_data(): void
    {
        $items = [
            ['name' => 'Product A', 'quantity' => 2, 'price' => 50.00],
            ['name' => 'Product B', 'quantity' => 1, 'price' => 100.00],
        ];

        $notification = new OrderConfirmationNotification(
            orderNumber: 'CMD-001',
            orderDate: '01/01/2024',
            totalAmount: 200.00,
            items: $items,
            trackingLink: 'https://example.com/track',
            customerName: 'Test Customer'
        );

        $data = $notification->getNotificationData();

        $this->assertEquals('CMD-001', $data['order_number']);
        $this->assertEquals(200.00, $data['total_amount']);
        $this->assertEquals(2, $data['items_count']);
    }

    /** @test */
    public function sms_verification_notification_uses_sms_channel(): void
    {
        $notification = new SmsVerificationNotification(
            code: '123456',
            expiresInMinutes: 10,
            userName: 'Test User'
        );

        $channels = $notification->via($this->client);

        $this->assertContains(
            \App\Notifications\Channels\SmsChannel::class,
            $channels
        );
    }

    /** @test */
    public function whatsapp_notification_uses_whatsapp_channel(): void
    {
        $notification = new WhatsAppOrderUpdateNotification(
            orderNumber: 'CMD-001',
            status: 'shipped',
            trackingUrl: 'https://example.com/track'
        );

        $channels = $notification->via($this->client);

        $this->assertContains(
            \App\Notifications\Channels\WhatsAppChannel::class,
            $channels
        );
    }

    /** @test */
    public function urgent_alert_notification_uses_multiple_channels(): void
    {
        $notification = new UrgentAlertNotification(
            alertTitle: 'Test Alert',
            alertMessage: 'This is a test alert',
            severity: 'critical',
            actionUrl: 'https://example.com/action'
        );

        $channels = $notification->via($this->user);

        $this->assertContains('mail', $channels);
        $this->assertContains('slack', $channels);
    }

    // =========================================
    // Tests du routage des queues
    // =========================================

    /** @test */
    public function notifications_are_routed_to_correct_queues(): void
    {
        $welcomeNotification = new WelcomeNotification(
            userName: 'Test',
            activationLink: 'https://example.com',
            companyName: 'Test'
        );

        $queues = $welcomeNotification->viaQueues();

        $this->assertEquals('emails', $queues['mail']);
        $this->assertEquals('sms', $queues[\App\Notifications\Channels\SmsChannel::class]);
        $this->assertEquals('whatsapp', $queues[\App\Notifications\Channels\WhatsAppChannel::class]);
        $this->assertEquals('telegram', $queues[\App\Notifications\Channels\TelegramChannel::class]);
    }

    /** @test */
    public function email_notifications_go_to_emails_queue(): void
    {
        Queue::fake();

        $notification = new WelcomeNotification(
            userName: 'Test User',
            activationLink: 'https://example.com/activate',
            companyName: 'Test Company'
        );

        $this->client->notify($notification);

        Queue::assertPushedOn('emails', \Illuminate\Notifications\SendQueuedNotifications::class);
    }

    // =========================================
    // Tests du mapping des canaux
    // =========================================

    /** @test */
    public function channel_mapping_works_correctly(): void
    {
        // Mail -> email
        $this->assertEquals('email', Communication::mapChannel('mail'));

        // SMS variants -> sms
        $this->assertEquals('sms', Communication::mapChannel('sms'));
        $this->assertEquals('sms', Communication::mapChannel('twilio'));
        $this->assertEquals('sms', Communication::mapChannel('vonage'));

        // WhatsApp -> whatsapp
        $this->assertEquals('whatsapp', Communication::mapChannel('whatsapp'));
        $this->assertEquals('whatsapp', Communication::mapChannel('App\Notifications\Channels\WhatsAppChannel'));

        // Other channels -> other
        $this->assertEquals('other', Communication::mapChannel('slack'));
        $this->assertEquals('other', Communication::mapChannel('telegram'));
        $this->assertEquals('other', Communication::mapChannel('database'));
    }

    // =========================================
    // Tests de la traçabilité
    // =========================================

    /** @test */
    public function communication_is_created_with_correct_data(): void
    {
        $communication = Communication::create([
            'communicable_type' => Client::class,
            'communicable_id' => $this->client->id,
            'channel' => 'email',
            'direction' => 'outbound',
            'subject' => 'Test Subject',
            'message' => 'Test Message',
            'sent_at' => now(),
            'status' => 'sent',
            'notification_type' => WelcomeNotification::class,
            'provider' => 'resend',
        ]);

        $this->assertDatabaseHas('communications', [
            'id' => $communication->id,
            'channel' => 'email',
            'direction' => 'outbound',
            'status' => 'sent',
        ]);
    }

    /** @test */
    public function communication_scopes_work_correctly(): void
    {
        // Créer plusieurs communications
        Communication::create([
            'communicable_type' => Client::class,
            'communicable_id' => $this->client->id,
            'channel' => 'email',
            'direction' => 'outbound',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        Communication::create([
            'communicable_type' => Client::class,
            'communicable_id' => $this->client->id,
            'channel' => 'sms',
            'direction' => 'outbound',
            'status' => 'failed',
            'sent_at' => now(),
        ]);

        Communication::create([
            'communicable_type' => User::class,
            'communicable_id' => $this->user->id,
            'channel' => 'email',
            'direction' => 'inbound',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Test des scopes
        $this->assertEquals(2, Communication::email()->count());
        $this->assertEquals(1, Communication::sms()->count());
        $this->assertEquals(2, Communication::outbound()->count());
        $this->assertEquals(1, Communication::inbound()->count());
        $this->assertEquals(2, Communication::sent()->count());
        $this->assertEquals(1, Communication::failed()->count());
        $this->assertEquals(2, Communication::forClient($this->client->id)->count());
        $this->assertEquals(1, Communication::forUser($this->user->id)->count());
    }

    /** @test */
    public function communication_accessors_return_correct_values(): void
    {
        $communication = Communication::create([
            'communicable_type' => Client::class,
            'communicable_id' => $this->client->id,
            'channel' => 'email',
            'direction' => 'outbound',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->assertEquals('📧', $communication->channel_icon);
        $this->assertEquals('⬆️', $communication->direction_icon);
        $this->assertEquals('✈️', $communication->status_icon);
        $this->assertEquals('blue', $communication->channel_badge_color);
        $this->assertEquals('blue', $communication->status_badge_color);
        $this->assertTrue($communication->is_email);
        $this->assertTrue($communication->is_outbound);
    }

    /** @test */
    public function communication_can_be_marked_as_sent(): void
    {
        $communication = Communication::create([
            'communicable_type' => Client::class,
            'communicable_id' => $this->client->id,
            'channel' => 'email',
            'direction' => 'outbound',
            'status' => 'pending',
            'sent_at' => now(),
        ]);

        $communication->markAsSent('resend');

        $this->assertEquals('sent', $communication->status);
        $this->assertEquals('resend', $communication->provider);
    }

    /** @test */
    public function communication_can_be_marked_as_failed(): void
    {
        $communication = Communication::create([
            'communicable_type' => Client::class,
            'communicable_id' => $this->client->id,
            'channel' => 'email',
            'direction' => 'outbound',
            'status' => 'pending',
            'sent_at' => now(),
        ]);

        $communication->markAsFailed('Connection timeout', 'resend');

        $this->assertEquals('failed', $communication->status);
        $this->assertNotNull($communication->failed_at);
        $this->assertEquals(1, $communication->retry_count);
        $this->assertArrayHasKey('errors', $communication->metadata);
    }

    // =========================================
    // Tests des préférences utilisateur
    // =========================================

    /** @test */
    public function notification_preference_can_be_created(): void
    {
        $preference = $this->client->getOrCreateNotificationPreference();

        $this->assertNotNull($preference);
        $this->assertTrue($preference->notify_email);
        $this->assertFalse($preference->notify_sms);
    }

    /** @test */
    public function enabled_channels_are_correctly_returned(): void
    {
        $preference = $this->client->getOrCreateNotificationPreference();
        $preference->update([
            'notify_email' => true,
            'notify_sms' => true,
            'phone_number' => '+33612345678',
        ]);

        $enabledChannels = $preference->getEnabledChannels();

        $this->assertContains('mail', $enabledChannels);
        $this->assertContains('sms', $enabledChannels);
    }

    /** @test */
    public function channel_can_be_enabled_and_disabled(): void
    {
        $preference = $this->client->getOrCreateNotificationPreference();

        $this->assertTrue($preference->isChannelEnabled('email'));

        $preference->disableChannel('email');
        $this->assertFalse($preference->isChannelEnabled('email'));

        $preference->enableChannel('email');
        $this->assertTrue($preference->isChannelEnabled('email'));
    }
}
