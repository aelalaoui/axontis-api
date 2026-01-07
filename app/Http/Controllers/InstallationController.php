<?php

namespace App\Http\Controllers;

use App\Models\Installation;
use App\Services\InstallationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class InstallationController extends Controller
{
    protected InstallationService $installationService;

    public function __construct(InstallationService $installationService)
    {
        $this->installationService = $installationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $installations = $this->installationService->getAllInstallations();
            return response()->json([
                'success' => true,
                'data' => $installations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve installations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * create a newly created resource in storage.
     */
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_uuid' => 'required|exists:clients,uuid',
            'city_id' => 'required|integer|exists:cities,id',
            'address' => 'required|string|min:3|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $installation = $this->installationService->createInstallation($request->all());
            return response()->json([
                'success' => true,
                'message' => 'Installation created successfully',
                'data' => $installation
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create installation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $installation = $this->installationService->findInstallationByUuid($uuid);

            if (!$installation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Installation not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $installation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve installation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_uuid' => 'sometimes|exists:clients,uuid',
            'contract_uuid' => 'sometimes|exists:contracts,uuid',
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $installation = $this->installationService->findInstallationByUuid($uuid);

            if (!$installation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Installation not found'
                ], 404);
            }

            $updatedInstallation = $this->installationService->updateInstallation($installation, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Installation updated successfully',
                'data' => $updatedInstallation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update installation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $installation = $this->installationService->findInstallationByUuid($uuid);

            if (!$installation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Installation not found'
                ], 404);
            }

            $this->installationService->deleteInstallation($installation);

            return response()->json([
                'success' => true,
                'message' => 'Installation deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete installation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Schedule installation date and time
     */
    public function schedule(Request $request, string $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'scheduled_date' => 'required|date|date_format:Y-m-d',
            'scheduled_time' => 'required|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $installation = Installation::fromUuid($uuid);

            if (is_null($installation)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Installation not found'
                ], 404);
            }

            // Get authenticated client from middleware
            $authenticatedClient = $request->get('client');

            // Verify that the installation belongs to the authenticated client
            if ($installation->client_id !== $authenticatedClient->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This installation does not belong to your account'
                ], 403);
            }

            // Validate client and contract status
            $client = $installation->client;
            $contract = $installation->contract;

            if (!$client || $client->status->value !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Client status must be active'
                ], 403);
            }

            if (is_null($contract) || $contract->status->value !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Contract status must be pending'
                ], 403);
            }

            // Validate date is within allowed range (J+3 to 1 month)
            $scheduledDateTime = new \DateTime($request->scheduled_date . ' ' . $request->scheduled_time);
            $today = new \DateTime();
            $minDate = (new \DateTime())->add(new \DateInterval('P3D'));
            $maxDate = (new \DateTime())->add(new \DateInterval('P30D'));

            if ($scheduledDateTime < $minDate || $scheduledDateTime > $maxDate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Installation date must be between J+3 and 1 month from today'
                ], 422);
            }

            $scheduledInstallation = $this->installationService->scheduleInstallation(
                $installation,
                $request->scheduled_date,
                $request->scheduled_time
            );

            return response()->json([
                'success' => true,
                'message' => 'Installation scheduled successfully',
                'data' => $scheduledInstallation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule installation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display installation schedule form
     */
    public function toSchedule(Request $request, string $uuid)
    {
        /** @var Installation $installation */
        $installation = Installation::fromUuid($uuid);

        if (is_null($installation)) {
            abort(404, 'Installation not found');
        }

        // Load related data
        $installation->load(['client', 'contract']);

        // Verify that the installation belongs to the authenticated client
        $authenticatedClient = $request->get('client');

        if ($installation->client_id !== $authenticatedClient->id) {
            abort(403, 'Unauthorized: This installation does not belong to your account');
        }

        return Inertia::render('Client/Operations/Schedule', [
            'installation' => [
                'uuid' => $installation->uuid,
                'address' => $installation->address,
                'zip_code' => '',
                'city' => $installation->city_fr,
            ],
            'client' => [
                'uuid' => $installation->client->uuid,
                'first_name' => $installation->client->first_name,
                'last_name' => $installation->client->last_name,
            ],
            'contract' => [
                'uuid' => $installation->contract->uuid,
            ],
        ]);
    }
}
