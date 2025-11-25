<?php

namespace Tests\Feature;

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ClientCreationTest extends TestCase
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

        $user = User::factory()->create();
        Sanctum::actingAs($user);

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

        $user = User::factory()->create();
        Sanctum::actingAs($user);

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
}
