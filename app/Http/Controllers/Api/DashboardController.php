<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Contract;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats(Request $request)
    {
        try {
            // Check if user is authenticated
            if (!$request->user()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Authentication required.'
                ], 401);
            }

            // Check if user has the required role (manager or administrator)
            if (!$request->user()->hasAnyRole([UserRole::MANAGER, UserRole::ADMINISTRATOR])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. Only managers and administrators can access dashboard statistics.'
                ], 403);
            }

            // Converted clients: status is 'paid', 'active', or 'formal_notice'
            $convertedClients = Client::whereIn('status', ['paid', 'active', 'formal_notice'])
                ->count();

            // Contracts with status: 'paid', 'active', 'scheduled', 'pending'
            $contractsCount = Contract::whereIn('status', ['paid', 'active', 'scheduled', 'pending'])
                ->count();

            // Monthly revenue: sum of monthly_amount_cents for contracts with status 'active'
            $monthlyRevenue = Contract::where('status', 'active')
                ->sum('monthly_amount_cents') / 100; // Convert cents to currency

            // Total clients
            $totalClients = Client::count();

            return response()->json([
                'success' => true,
                'data' => [
                    'convertedClients' => $convertedClients,
                    'activeContracts' => $contractsCount,
                    'monthlyRevenue' => $monthlyRevenue,
                    'totalClients' => $totalClients,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}

