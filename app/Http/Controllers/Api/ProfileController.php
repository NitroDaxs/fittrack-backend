<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json($request->user()->only(['id', 'name', 'email', 'role', 'units', 'created_at']));
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'units' => 'sometimes|in:metric,imperial',
            'current_password' => 'required_with:new_password|string',
            'new_password' => 'sometimes|string|min:8|confirmed',
        ]);

        if (isset($validated['new_password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json(['message' => 'Current password is incorrect'], 422);
            }

            $user->password = $validated['new_password'];
        }

        $user->fill(collect($validated)->only(['name', 'email', 'units'])->toArray());
        $user->save();

        return response()->json($user->only(['id', 'name', 'email', 'role', 'units']));
    }

    public function favorites(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'routines' => $user->savedRoutines()->get(['routines.id', 'title', 'slug', 'goal', 'level']),
            'exercises' => $user->savedExercises()->get(['exercises.id', 'name', 'slug', 'difficulty']),
        ]);
    }
}
