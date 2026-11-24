<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function staff(UserRole $role = UserRole::MANAGER): User
    {
        return User::factory()->create(['role' => $role->value]);
    }

    // ─── Paiements : lecture et remboursement ────────────────

    public function test_refund_is_refused_to_anonymous_and_client(): void
    {
        $this->mock(PaymentService::class)->shouldNotReceive('refundPayment');

        $this->postJson('/api/payments/some-uuid/refund')->assertStatus(302);
        $this->actingAs($this->staff(UserRole::CLIENT))
            ->postJson('/api/payments/some-uuid/refund')->assertStatus(302);
    }

    public function test_refund_works_for_manager(): void
    {
        $this->mock(PaymentService::class)
            ->shouldReceive('refundPayment')->once()->with('some-uuid', null)
            ->andReturn(['success' => true, 'message' => 'Refunded', 'data' => []]);

        $this->actingAs($this->staff())
            ->postJson('/api/payments/some-uuid/refund')
            ->assertOk()->assertJson(['success' => true]);
    }

    public function test_payment_details_are_refused_to_anonymous_and_allowed_to_administrator(): void
    {
        $this->mock(PaymentService::class)
            ->shouldReceive('getPaymentDetails')->once()
            ->andReturn(['success' => true, 'data' => ['payment_uuid' => 'some-uuid']]);

        $this->getJson('/api/payments/some-uuid')->assertStatus(302);
        $this->actingAs($this->staff(UserRole::ADMINISTRATOR))
            ->getJson('/api/payments/some-uuid')->assertOk();
    }
}
