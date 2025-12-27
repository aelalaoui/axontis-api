<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Process payment for a contract
     */
    public function processPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_uuid' => 'required|string',
            'contract_uuid' => 'required|string',
            'card_number' => 'required|string',
            'card_holder' => 'required|string',
            'expiry_date' => 'required|string',
            'cvv' => 'required|string|size:3',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->paymentService->processPayment([
                'client_uuid' => $request->client_uuid,
                'contract_uuid' => $request->contract_uuid,
                'card_number' => $request->card_number,
                'card_holder' => $request->card_holder,
                'expiry_date' => $request->expiry_date,
                'cvv' => $request->cvv,
                'amount' => $request->amount,
            ]);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment processed successfully',
                    'data' => $result['data'],
                    'redirect_url' => route('register')
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Payment failed',
                'error' => $result['error'] ?? null
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

