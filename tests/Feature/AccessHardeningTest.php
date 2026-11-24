<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\SignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(UserRole $role = UserRole::MANAGER): User
    {
        return User::factory()->create(['role' => $role->value]);
    }

    // ─── Paiements : lecture et remboursement ────────────────

    public function test_refund_is_refused_to_anonymous_and_client(): void
    {
        $this->mock(PaymentService::class)->shouldNotReceive('refundPayment');

        $this->postJson('/api/payments/some-uuid/refund')->assertStatus(302);
        $this->actingAs($this->userWithRole(UserRole::CLIENT))
            ->postJson('/api/payments/some-uuid/refund')->assertStatus(302);
    }

    public function test_refund_works_for_manager(): void
    {
        $this->mock(PaymentService::class)
            ->shouldReceive('refundPayment')->once()->with('some-uuid', null)
            ->andReturn(['success' => true, 'message' => 'Refunded', 'data' => []]);

        $this->actingAs($this->userWithRole())
            ->postJson('/api/payments/some-uuid/refund')
            ->assertOk()->assertJson(['success' => true]);
    }

    public function test_payment_details_are_refused_to_anonymous_and_allowed_to_administrator(): void
    {
        $this->mock(PaymentService::class)
            ->shouldReceive('getPaymentDetails')->once()
            ->andReturn(['success' => true, 'data' => ['payment_uuid' => 'some-uuid']]);

        $this->getJson('/api/payments/some-uuid')->assertStatus(302);
        $this->actingAs($this->userWithRole(UserRole::ADMINISTRATOR))
            ->getJson('/api/payments/some-uuid')->assertOk();
    }

    // ─── Pages et API internes ───────────────────────────────

    public function test_signature_webhooks_page_is_staff_only(): void
    {
        $this->mock(SignatureService::class); // le vrai service exige la configuration DocuSign
        $this->get('/signature/webhooks')->assertRedirect(route('login'));
        $this->actingAs($this->userWithRole(UserRole::CLIENT))->get('/signature/webhooks')->assertRedirect(route('dashboard'));
        $this->actingAs($this->userWithRole())->get('/signature/webhooks')->assertOk();
    }

    public function test_entity_search_is_refused_to_anonymous_and_client(): void
    {
        $url = '/api/entities/search?type=' . urlencode(User::class) . '&query=a';

        $this->getJson($url)->assertStatus(302);
        $this->getJson('/api/entities/types')->assertStatus(302);
        $this->actingAs($this->userWithRole(UserRole::CLIENT))->getJson($url)->assertStatus(302);
    }

    public function test_entity_search_works_for_internal_user(): void
    {
        $this->userWithRole(UserRole::ADMINISTRATOR)->update(['name' => 'Alice Admin']);

        $this->actingAs($this->userWithRole(UserRole::TECHNICIAN))
            ->getJson('/api/entities/search?type=' . urlencode(User::class) . '&query=Alice')
            ->assertOk()
            ->assertSee('Alice Admin');
    }
}
