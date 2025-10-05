<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    /**
     * Create a new client
     */
    public function create(Request $request): JsonResponse
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:clients,email',
            'country' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Créer le nouveau client
            $client = Client::create([
                'email' => $request->email,
                'country' => $request->country,
                'type' => 'unknown',
                'status' => 'email_step'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Client created successfully',
                'data' => $client
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create client',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
