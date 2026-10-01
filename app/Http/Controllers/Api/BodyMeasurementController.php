<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BodyMeasurementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $measurements = $request->user()->bodyMeasurements()
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($measurements);
    }

    public function store(Request $request): JsonResponse
    {
        // 1. Default 'date' to today if not provided
        if (!$request->has('date') || empty($request->input('date'))) {
            $request->merge(['date' => now()->toDateString()]);
        }

        // 2. Map 'bodyFat' (camelCase from frontend) to 'body_fat_pct'
        if ($request->has('bodyFat') && !$request->has('body_fat_pct')) {
            $request->merge(['body_fat_pct' => $request->input('bodyFat')]);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'weight' => 'nullable|numeric',
            'body_fat_pct' => 'nullable|numeric',
            'custom_measurements' => 'nullable|array',
            'photo_url' => 'nullable|string',
        ]);

        // 3. Extract top-level body metrics into 'custom_measurements' JSON if not explicitly nested
        $customParts = $request->only(['waist', 'chest', 'arms', 'shoulders', 'hips', 'thighs', 'calves']);
        if (!empty($customParts)) {
            $existingCustom = $validated['custom_measurements'] ?? [];
            $validated['custom_measurements'] = array_merge($existingCustom, $customParts);
        }

        $measurement = $request->user()->bodyMeasurements()->create($validated);

        return response()->json($measurement, 201);
    }
}
