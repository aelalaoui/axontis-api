<?php

namespace Tests\Feature;

use App\Enums\ClientStatus;
use App\Enums\ClientStep;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a new client can be created and only its UUID is returned.
     *
     * @return void
     */
    public function test_new_client_can_be_created()
    {
        $this->withoutExceptionHandling();

        $response = $this->postJson('/api/client/new', [
            'email' => 'test@example.com',
            'country' => 'France',
        ]);

        $client = Client::where('email', 'test@example.com')->firstOrFail();

        $response->assertStatus(201)
            ->assertExactJson([
                'success' => true,
                'message' => 'Client created successfully',
                'data' => ['uuid' => $client->uuid],
            ]);
    }

    /**
     * A known e-mail must not reveal the existing client (no UUID, no details).
     *
     * @return void
     */
    public function test_existing_email_does_not_leak_existing_client()
    {
        $this->withoutExceptionHandling();

        $existing = Client::create([
            'email' => 'existing@example.com',
            'country' => 'Germany',
            'first_name' => 'Secret',
            'type' => 'unknown',
            'step' => ClientStep::EMAIL_STEP,
            'status' => ClientStatus::CREATED
        ]);

        $response = $this->postJson('/api/client/new', [
            'email' => 'existing@example.com',
            'country' => 'Germany',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Client created successfully')
            ->assertJsonMissing(['first_name' => 'Secret'])
            ->assertJsonMissing(['email' => 'existing@example.com']);

        $this->assertNotEquals($existing->uuid, $response->json('data.uuid'));
        $this->assertCount(2, Client::where('email', 'existing@example.com')->get());
    }

    private function clientWithContract(string $email): array
    {
        $client = Client::create([
            'email' => $email,
            'country' => 'MA',
            'first_name' => 'Jean',
            'last_name' => 'Client',
            'type' => 'individual',
            'step' => ClientStep::PAYMENT_STEP,
            'status' => ClientStatus::CREATED,
        ]);
        $contract = Contract::create([
            'client_uuid' => $client->uuid,
            'start_date' => now(),
            'status' => 'paid',
        ]);

        return [$client, $contract];
    }

    public function test_create_account_creates_client_user_and_logs_in()
    {
        [$client, $contract] = $this->clientWithContract('new-client@example.com');

        $this->post('/client/create-account', [
            'client_uuid' => $client->uuid,
            'contract_uuid' => $contract->uuid,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('client.home'));

        $user = User::where('email', 'new-client@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->isClient());
        $this->assertEquals($user->id, $client->fresh()->user_id);
    }

    public function test_create_account_refuses_existing_account_without_login_or_downgrade()
    {
        $staff = User::factory()->create(['email' => 'staff@example.com', 'role' => UserRole::MANAGER->value]);
        [$client, $contract] = $this->clientWithContract('staff@example.com');

        $this->post('/client/create-account', [
            'client_uuid' => $client->uuid,
            'contract_uuid' => $contract->uuid,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertEquals(UserRole::MANAGER, $staff->fresh()->role);
        $this->assertNull($client->fresh()->user_id);
    }

    public function test_create_account_refuses_contract_of_another_client()
    {
        [$client] = $this->clientWithContract('a@example.com');
        [, $otherContract] = $this->clientWithContract('b@example.com');

        $this->post('/client/create-account', [
            'client_uuid' => $client->uuid,
            'contract_uuid' => $otherContract->uuid,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('contract_uuid');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'a@example.com']);
    }

    /**
     * Test that criterias can be stored for a client.
     *
     * @return void
     */
    public function test_criterias_can_be_stored()
    {
        $this->withoutExceptionHandling();

        // Create a client first
        $client = Client::create([
            'email' => 'client@example.com',
            'country' => 'France',
            'type' => 'unknown',
            'step' => ClientStep::EMAIL_STEP
        ]);

        // Store criterias
        $response = $this->postJson("/api/client/{$client->uuid}/store-criterias", [
            'criterias' => [
                'customerType' => 'individual',
                'age' => 30,
                'city' => 'Paris',
                'hasChildren' => true,
            ]
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Criterias stored successfully',
                'data' => [
                    'client_uuid' => $client->uuid,
                    'criterias_count' => 4,
                ]
            ]);

        // Verify properties were stored
        $client->refresh();
        $this->assertEquals('individual', $client->getProperty('customerType'));
        $this->assertEquals(30, $client->getProperty('age'));
        $this->assertEquals('Paris', $client->getProperty('city'));
        $this->assertEquals(true, $client->getProperty('hasChildren'));
        $this->assertEquals('individual', $client->type);
    }

    /**
     * Test that storing criterias replaces old ones.
     *
     * @return void
     */
    public function test_storing_criterias_replaces_old_ones()
    {
        $this->withoutExceptionHandling();

        // Create a client with initial properties
        $client = Client::create([
            'email' => 'client@example.com',
            'country' => 'France',
            'type' => 'unknown',
            'step' => ClientStep::EMAIL_STEP
        ]);

        $client->setProperty('oldProperty', 'oldValue');
        $client->setProperty('customerType', 'business');

        // Store new criterias
        $response = $this->postJson("/api/client/{$client->uuid}/store-criterias", [
            'criterias' => [
                'customerType' => 'individual',
                'newProperty' => 'newValue',
            ]
        ]);

        $response->assertStatus(200);

        // Verify old properties were cleared
        $client->refresh();
        $this->assertNull($client->getProperty('oldProperty'));
        $this->assertEquals('individual', $client->getProperty('customerType'));
        $this->assertEquals('newValue', $client->getProperty('newProperty'));
    }

    /**
     * Test that email is ignored when storing criterias.
     *
     * @return void
     */
    public function test_email_is_ignored_in_criterias()
    {
        $this->withoutExceptionHandling();

        // Create a client
        $client = Client::create([
            'email' => 'original@example.com',
            'country' => 'France',
            'type' => 'unknown',
            'step' => ClientStep::EMAIL_STEP
        ]);

        // Try to store email in criterias
        $response = $this->postJson("/api/client/{$client->uuid}/store-criterias", [
            'criterias' => [
                'email' => 'changed@example.com',
                'customerType' => 'individual',
            ]
        ]);

        $response->assertStatus(200);

        // Verify email was not stored as property
        $client->refresh();
        $this->assertNull($client->getProperty('email'));
        $this->assertEquals('original@example.com', $client->email);
    }

    /**
     * Test that storing criterias fails for non-existent client.
     *
     * @return void
     */
    public function test_storing_criterias_fails_for_nonexistent_client()
    {
        $response = $this->postJson("/api/client/non-existent-uuid/store-criterias", [
            'criterias' => [
                'customerType' => 'individual',
            ]
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Client not found'
            ]);
    }

    /**
     * Test that storing criterias requires criterias array.
     *
     * @return void
     */
    public function test_storing_criterias_requires_criterias_array()
    {
        $client = Client::create([
            'email' => 'client@example.com',
            'country' => 'France',
            'type' => 'unknown',
            'step' => ClientStep::EMAIL_STEP
        ]);

        $response = $this->postJson("/api/client/{$client->uuid}/store-criterias", []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }
}
