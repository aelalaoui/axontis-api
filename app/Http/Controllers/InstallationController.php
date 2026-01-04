<?php

namespace App\Http\Controllers;

use App\Services\InstallationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
}
