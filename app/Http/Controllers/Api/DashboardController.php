<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
            // Cache key for dashboard stats (1 hours = 3600 seconds)
            $cacheKey = 'dashboard_stats';
            $cacheDuration = 3600; // 1 hour in seconds

            // Try to get from cache first
            $cachedData = Cache::get($cacheKey);
            if ($cachedData !== null) {
                return response()->json([
                    'success' => true,
                    'data' => $cachedData,
                    'cached' => true
                ], 200);
            }

            // Converted clients: status is 'paid', 'active', or 'formal_notice'
            $convertedClients = Client::whereIn('status', ['paid', 'active', 'formal_notice'])
                ->count();

            // Contracts with status: 'paid', 'active', 'scheduled', 'pending'
            $activeContracts = Contract::whereIn('status', ['paid', 'active', 'scheduled', 'pending'])
                ->count();

            // Monthly revenue: sum of monthly_amount_cents for contracts with status 'active'
            $monthlyRevenue = Contract::where('status', 'active')
                ->sum('monthly_amount_cents') / 100; // Convert cents to currency

            // Total clients
            $totalClients = Client::count();

            // Revenue data by day (last 30 days) - only active contracts
            $revenueDayData = $this->getRevenueByDay();

            // Revenue data by month (last 12 months) - only active contracts
            $revenueMonthData = $this->getRevenueByMonth();

            // Client growth by day (last 30 days) - converted clients
            $clientDayData = $this->getClientGrowthByDay();

            // Client growth by month (last 12 months) - converted clients
            $clientMonthData = $this->getClientGrowthByMonth();

            $data = [
                'convertedClients' => $convertedClients,
                'activeContracts' => $activeContracts,
                'monthlyRevenue' => $monthlyRevenue,
                'totalClients' => $totalClients,
                'revenueDayData' => $revenueDayData,
                'revenueMonthData' => $revenueMonthData,
                'clientDayData' => $clientDayData,
                'clientMonthData' => $clientMonthData,
            ];

            // Store in cache for 4 hours
            Cache::put($cacheKey, $data, $cacheDuration);

            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get revenue data by day for the last 30 days (active contracts only)
     */
    private function getRevenueByDay()
    {
        return Cache::remember('dashboard_revenue_day', 14400, function () {
            $data = [];
            // Go from 29 days ago to now (oldest to newest)
            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dateString = $date->toDateString();
                $revenue = Contract::where('status', 'active')
                    ->whereDate('created_at', $dateString)
                    ->sum('monthly_amount_cents') / 100;

                $data[] = [
                    'date' => $dateString,
                    'label' => $date->format('M d'),
                    'revenue' => round($revenue, 2),
                ];
            }
            return $data;
        });
    }

    /**
     * Get revenue data by month for the last 12 months (active contracts only)
     */
    private function getRevenueByMonth()
    {
        return Cache::remember('dashboard_revenue_month', 14400, function () {
            $data = [];
            // Go from 11 months ago to now (oldest to newest)
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $startOfMonth = $date->copy()->startOfMonth()->toDateString();
                $endOfMonth = $date->copy()->endOfMonth()->toDateString();

                $revenue = Contract::where('status', 'active')
                    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->sum('monthly_amount_cents') / 100;

                $data[] = [
                    'month' => $date->format('Y-m'),
                    'label' => $date->format('M Y'),
                    'revenue' => round($revenue, 2),
                ];
            }
            return $data;
        });
    }

    /**
     * Get client growth by day for the last 30 days (converted clients only)
     */
    private function getClientGrowthByDay()
    {
        return Cache::remember('dashboard_client_day', 14400, function () {
            $data = [];
            // Go from 29 days ago to now (oldest to newest)
            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dateString = $date->toDateString();
                $count = Client::whereIn('status', ['paid', 'active', 'formal_notice'])
                    ->whereDate('created_at', $dateString)
                    ->count();

                $data[] = [
                    'date' => $dateString,
                    'label' => $date->format('M d'),
                    'count' => $count,
                ];
            }
            return $data;
        });
    }

    /**
     * Get client growth by month for the last 12 months (converted clients only)
     */
    private function getClientGrowthByMonth()
    {
        return Cache::remember('dashboard_client_month', 14400, function () {
            $data = [];
            // Go from 11 months ago to now (oldest to newest)
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $startOfMonth = $date->copy()->startOfMonth()->toDateString();
                $endOfMonth = $date->copy()->endOfMonth()->toDateString();

                $count = Client::whereIn('status', ['paid', 'active', 'formal_notice'])
                    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->count();

                $data[] = [
                    'month' => $date->format('Y-m'),
                    'label' => $date->format('M Y'),
                    'count' => $count,
                ];
            }
            return $data;
        });
    }

    /**
     * Get chart data for dashboard
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChartData(Request $request)
    {
        try {
            // Cache key for chart data (4 hours = 14400 seconds)
            $cacheKey = 'dashboard_charts';
            $cacheDuration = 14400;

            // Try to get from cache first
            $cachedCharts = Cache::get($cacheKey);
            if ($cachedCharts !== null) {
                return response()->json([
                    'success' => true,
                    'data' => $cachedCharts,
                    'cached' => true
                ], 200);
            }

            $charts = [
                'revenueDay' => $this->getRevenueByDay(),
                'revenueMonth' => $this->getRevenueByMonth(),
                'clientGrowthDay' => $this->getClientGrowthByDay(),
                'clientGrowthMonth' => $this->getClientGrowthByMonth(),
            ];

            // Store in cache for 4 hours
            Cache::put($cacheKey, $charts, $cacheDuration);

            return response()->json([
                'success' => true,
                'data' => $charts
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving chart data: ' . $e->getMessage()
            ], 500);
        }
    }
}
