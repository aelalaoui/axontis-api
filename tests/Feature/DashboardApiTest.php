<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    /**
     * Test that unauthenticated users cannot access dashboard stats
     */
    public function test_dashboard_stats_returns_401_for_unauthenticated_users(): void
    {
        $response = $this->getJson('/api/dashboard/stats');

        $response->assertStatus(401);
    }

    /**
     * Test that non-manager users cannot access dashboard stats
     */
    public function test_dashboard_stats_returns_403_for_non_manager_users(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::CLIENT->value
        ]);

        $response = $this->actingAs($user)->getJson('/api/dashboard/stats');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test that manager users can access dashboard stats
     */
    public function test_dashboard_stats_api_returns_success_response_for_managers(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::MANAGER->value
        ]);

        $response = $this->actingAs($user)->getJson('/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'convertedClients',
                    'activeContracts',
                    'monthlyRevenue',
                    'totalClients',
                ]
            ]);
    }

    /**
     * Test that administrator users can access dashboard stats
     */
    public function test_dashboard_stats_api_returns_success_response_for_administrators(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMINISTRATOR->value
        ]);

        $response = $this->actingAs($user)->getJson('/api/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'convertedClients',
                    'activeContracts',
                    'monthlyRevenue',
                    'totalClients',
                ]
            ]);
    }

    /**
     * Test that the dashboard stats returns correct data types for managers
     */
    public function test_dashboard_stats_returns_correct_data_types(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::MANAGER->value
        ]);

        $response = $this->actingAs($user)->getJson('/api/dashboard/stats');

        $response->assertJson([
            'success' => true,
        ]);

        // Verify data types
        $data = $response->json('data');
        $this->assertIsInt($data['convertedClients']);
        $this->assertIsInt($data['activeContracts']);
        $this->assertIsInt($data['totalClients']);
        $this->assertIsNumeric($data['monthlyRevenue']);
    }
}

