<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Communication;
use App\Models\Contract;
use App\Models\Installation;
use App\Models\Task;
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
     * Get scheduled contracts for dashboard upcoming tasks
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getScheduledContracts(Request $request)
    {
        try {
            $contracts = Contract::with('client')
                ->where('status', \App\Enums\ContractStatus::SCHEDULED->value)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($contract) {
                    return [
                        'uuid' => $contract->uuid,
                        'description' => $contract->description,
                        'status' => $contract->status,
                        'client_name' => $contract->client ? $contract->client->full_name : 'N/A',
                        'client_uuid' => $contract->client_uuid,
                        'monthly_amount' => $contract->monthly_amount_cents / 100,
                        'currency' => $contract->currency,
                        'start_date' => $contract->start_date,
                        'created_at' => $contract->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $contracts
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving scheduled contracts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/dashboard/pending-tasks
     * - manager / administrator : tâches non-assignées en priorité
     * - technician / operator / accountant / storekeeper : uniquement leurs tâches assignées
     */
    public function getPendingTasks(Request $request)
    {
        try {
            $user = $request->user();

            $restrictedRoles = [
                UserRole::OPERATOR,
                UserRole::STOREKEEPER,
                UserRole::ACCOUNTANT,
                UserRole::TECHNICIAN
            ];
            $isRestricted    = $user && in_array($user->role, $restrictedRoles);

            $query = Task::with(['user:id,name', 'taskable'])
                ->whereIn('status', ['scheduled', 'in_progress']);

            if ($isRestricted) {
                // L'utilisateur ne voit que ses propres tâches
                $query->where('user_id', $user->id)
                      ->orderBy('scheduled_date', 'asc')
                      ->orderBy('created_at', 'desc');
            } else {
                // Managers / admins : tâches non-assignées en priorité
                $query->orderByRaw("CASE WHEN user_id IS NULL THEN 0 ELSE 1 END")
                      ->orderBy('created_at', 'desc');
            }

            $tasks = $query->limit(8)->get()
                ->map(function (Task $task) {
                    $installationMode = null;
                    $deliveryAddress  = null;
                    $clientName       = null;
                    $clientUuid       = null;

                    if ($task->taskable instanceof Installation) {
                        $task->taskable->loadMissing('client.properties');
                        if ($task->taskable->client) {
                            $installationMode = $task->taskable->client->getProperty('installation_mode');
                            $deliveryAddress  = $task->taskable->client->getProperty('delivery_address');
                            $clientName       = $task->taskable->client->full_name;
                            $clientUuid       = $task->taskable->client->uuid;
                        }
                    }

                    return [
                        'uuid'              => $task->uuid,
                        'type'              => $task->type,
                        'status'            => $task->status,
                        'address'           => $task->address,
                        'notes'             => $task->notes,
                        'scheduled_date'    => $task->scheduled_date?->format('Y-m-d'),
                        'installation_mode' => $installationMode,
                        'delivery_address'  => $deliveryAddress,
                        'client_name'       => $clientName,
                        'client_uuid'       => $clientUuid,
                        'taskable_uuid'     => $task->taskable_uuid,
                        'technician'        => $task->user ? ['id' => $task->user->id, 'name' => $task->user->name] : null,
                        'created_at'        => $task->created_at->format('Y-m-d H:i'),
                    ];
                });

            return response()->json(['success' => true, 'data' => $tasks], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/dashboard/my-communications
     * Retourne les 8 dernières communications liées au technicien connecté.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMyRecentCommunications(Request $request)
    {
        try {
            $user = $request->user();

            $communications = Communication::where(function ($q) use ($user) {
                    $q->where('communicable_type', \App\Models\User::class)
                      ->where('communicable_id', $user->id);
                })
                ->orWhere('handled_by', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(8)
                ->get()
                ->map(function (Communication $comm) {
                    return [
                        'id'          => $comm->id,
                        'uuid'        => $comm->uuid,
                        'channel'     => $comm->channel,
                        'direction'   => $comm->direction,
                        'subject'     => $comm->subject,
                        'message'     => $comm->getMessageExcerpt(80),
                        'status'      => $comm->status,
                        'sent_at'     => $comm->sent_at?->diffForHumans(),
                        'created_at'  => $comm->created_at?->diffForHumans(),
                        'channel_icon' => $comm->channel_icon,
                        'status_icon'  => $comm->status_icon,
                    ];
                });

            return response()->json(['success' => true, 'data' => $communications], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
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
