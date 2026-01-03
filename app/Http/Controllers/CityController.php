<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function autocomplete(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'string|max:255',
        ]);

        $search = $request->query('search', '');

        $cities = City::query()->where('city', 'LIKE', "%{$search}%")
            ->where('country_code', 'MA')
            ->select('id', 'city', 'postal_code', 'region', 'prefecture')
            ->get();

        return response()->json([
            'data' => $cities,
            'count' => $cities->count(),
        ]);
    }
}
