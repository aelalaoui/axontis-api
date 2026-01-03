<?php

namespace App\Http\Controllers;

use App\Http\Resources\CityTransformer;
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

        $cities = City::query()
            ->where('name_en', 'LIKE', "%{$search}%")
            ->orWhere('name_fr', 'LIKE', "%{$search}%")
            ->orWhere('name_ar', 'LIKE', "%{$search}%")
            ->with('region')
            ->select('id', 'region_id', 'name_ar', 'name_en', 'name_fr')
            ->get();

        return response()->json([
            'data' => CityTransformer::collection($cities),
            'count' => $cities->count(),
        ]);
    }
}
