<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    /**
     * Test that unauthenticated users get redirected (302) when accessing dashboard stats
     */
    public function test_dashboard_stats_returns_redirect_for_unauthenticated_users(): void
    {
        $response = $this->getJson('/api/dashboard/stats');

        // The route uses 'web' middleware which redirects unauthenticated users
        $response->assertStatus(302);
    }

    /**
     * Test that non-manager users get redirected (302) when accessing dashboard stats
     */
    public function test_dashboard_stats_returns_redirect_for_non_manager_users(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::CLIENT->value
        ]);

        $response = $this->actingAs($user)->getJson('/api/dashboard/stats');

        // The 'role' middleware redirects non-manager users
        $response->assertStatus(302);
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
                    'revenueDayData',
                    'revenueMonthData',
                    'clientDayData',
                    'clientMonthData',
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
                    'revenueDayData',
                    'revenueMonthData',
                    'clientDayData',
                    'clientMonthData',
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
        $this->assertIsArray($data['revenueDayData']);
        $this->assertIsArray($data['revenueMonthData']);
        $this->assertIsArray($data['clientDayData']);
        $this->assertIsArray($data['clientMonthData']);
    }

    /**
     * Test that chart data endpoint returns correct structure for managers
     */
    public function test_dashboard_charts_api_returns_correct_structure_for_managers(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::MANAGER->value
        ]);

        $response = $this->actingAs($user)->getJson('/api/dashboard/charts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'revenueDay',
                    'revenueMonth',
                    'clientGrowthDay',
                    'clientGrowthMonth',
                ]
            ]);
    }

    /**
     * Test that chart data contains proper fields
     */
    public function test_dashboard_charts_contains_proper_fields(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::MANAGER->value
        ]);

        $response = $this->actingAs($user)->getJson('/api/dashboard/charts');

        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json('data');

        // Verify array structures
        $this->assertIsArray($data['revenueDay']);
        $this->assertIsArray($data['revenueMonth']);
        $this->assertIsArray($data['clientGrowthDay']);
        $this->assertIsArray($data['clientGrowthMonth']);

        // Verify first element has required fields if not empty
        if (!empty($data['revenueDay'])) {
            $this->assertArrayHasKey('date', $data['revenueDay'][0]);
            $this->assertArrayHasKey('label', $data['revenueDay'][0]);
            $this->assertArrayHasKey('revenue', $data['revenueDay'][0]);
        }

        if (!empty($data['clientGrowthDay'])) {
            $this->assertArrayHasKey('date', $data['clientGrowthDay'][0]);
            $this->assertArrayHasKey('label', $data['clientGrowthDay'][0]);
            $this->assertArrayHasKey('count', $data['clientGrowthDay'][0]);
        }
    }

    /**
     * Test that chart data contains 12 months of data
     */
    public function test_dashboard_charts_contains_12_months_of_data(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::MANAGER->value
        ]);

        $response = $this->actingAs($user)->getJson('/api/dashboard/charts');

        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json('data');

        // Verify we have 12 months of data
        $this->assertCount(12, $data['revenueMonth']);
        $this->assertCount(12, $data['clientGrowthMonth']);

        // Verify months are in chronological order (oldest to newest)
        $months = array_map(fn($item) => $item['month'], $data['revenueMonth']);
        $sortedMonths = $months;
        sort($sortedMonths);
        $this->assertEquals($sortedMonths, $months, 'Months should be in chronological order');
    }

    /**
     * Test that chart data contains 30 days of data
     */
    public function test_dashboard_charts_contains_30_days_of_data(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::MANAGER->value
        ]);

        $response = $this->actingAs($user)->getJson('/api/dashboard/charts');

        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json('data');

        // Verify we have 30 days of data
        $this->assertCount(30, $data['revenueDay']);
        $this->assertCount(30, $data['clientGrowthDay']);

        // Verify days are in chronological order (oldest to newest)
        $days = array_map(fn($item) => $item['date'], $data['revenueDay']);
        $sortedDays = $days;
        sort($sortedDays);
        $this->assertEquals($sortedDays, $days, 'Days should be in chronological order');
    }
}

