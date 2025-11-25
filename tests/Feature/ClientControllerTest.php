<?php

namespace Tests\Feature;

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ClientControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a new client can be created.
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

        if ($response->status() !== 201) {
            // dump($response->json());
        }

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Client created successfully',
                'data' => [
                    'email' => 'test@example.com',
                    'country' => 'France',
                ]
            ]);

        $this->assertDatabaseHas('clients', [
            'email' => 'test@example.com',
        ]);
    }

    /**
     * Test that an existing client is returned if email already exists.
     *
     * @return void
     */
    public function test_existing_client_is_returned()
    {
        $this->withoutExceptionHandling();

        // Create a client first
        $client = Client::create([
            'email' => 'existing@example.com',
            'country' => 'Germany',
            'type' => 'unknown',
            'status' => ClientStatus::EMAIL_STEP
        ]);

        // Try to create the same client again
        $response = $this->postJson('/api/client/new', [
            'email' => 'existing@example.com',
            'country' => 'Germany',
        ]);

        if ($response->status() !== 200) {
            dump($response->json());
        }

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Client already exists',
                'data' => [
                    'email' => 'existing@example.com',
                    'country' => 'Germany',
                    'uuid' => $client->uuid
                ]
            ]);

        // Ensure no duplicate was created
        $this->assertCount(1, Client::where('email', 'existing@example.com')->get());
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
            'status' => ClientStatus::EMAIL_STEP
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
            'status' => ClientStatus::EMAIL_STEP
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
            'status' => ClientStatus::EMAIL_STEP
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
            'status' => ClientStatus::EMAIL_STEP
        ]);

        $response = $this->postJson("/api/client/{$client->uuid}/store-criterias", []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }
}
