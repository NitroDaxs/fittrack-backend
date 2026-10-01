<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminUserController extends Controller
{
    private const SORTABLE = ['name', 'email', 'role', 'created_at'];

    public function index(Request $request): JsonResponse
    {
        $query = User::query()->select(['id', 'name', 'email', 'role', 'units', 'is_active', 'created_at']);

        // The original closure chained orWhere without grouping, so a search
        // combined with any other filter would leak rows past that filter.
        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = $request->input('search');
            $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        });

        $query->when($request->filled('role'), function ($q) use ($request) {
            $role = strtolower($request->input('role'));
            if (in_array($role, ['member', 'admin'], true)) {
                $q->where('role', $role);
            }
        });

        $query->when($request->filled('status'), function ($q) use ($request) {
            $status = strtolower($request->input('status'));
            if ($status === 'active') {
                $q->where('is_active', true);
            } elseif (in_array($status, ['suspended', 'inactive'], true)) {
                $q->where('is_active', false);
            }
        });

        $sort = in_array($request->input('sort'), self::SORTABLE, true)
            ? $request->input('sort')
            : 'created_at';
        $dir = $request->input('dir') === 'asc' ? 'asc' : 'desc';

        $perPage = $request->input('perPage', $request->input('per_page', 20));

        return response()->json($query->orderBy($sort, $dir)->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|in:member,admin',
            'is_active' => 'sometimes|boolean',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            // Admin-created accounts get a random password; the person is
            // expected to reset it rather than be handed one.
            'password' => $validated['password'] ?? Str::random(32),
            'role' => $validated['role'] ?? 'member',
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json($this->present($user), 201);
    }

    /**
     * Single update path for the admin table, which edits role and active state
     * through one form. The narrower updateRole/toggleActive routes remain for
     * callers that only need one field.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'role' => 'sometimes|in:member,admin',
            'is_active' => 'sometimes|boolean',
        ]);

        $this->guardSelfDemotion($request, $user, $validated);

        $user->fill($validated)->save();

        return response()->json($this->present($user));
    }

    public function updateRole(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate(['role' => 'required|in:member,admin']);

        $this->guardSelfDemotion($request, $user, $validated);

        $user->update(['role' => $validated['role']]);

        return response()->json($this->present($user));
    }

    public function toggleActive(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'You cannot deactivate your own account.'], 422);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json($this->present($user));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        $user->delete();

        return response()->json(['status' => 'deleted']);
    }

    /** Stops an admin locking themselves out of the console mid-session. */
    private function guardSelfDemotion(Request $request, User $user, array $validated): void
    {
        if (
            $request->user()->id === $user->id
            && ($validated['role'] ?? null) === 'member'
        ) {
            abort(422, 'You cannot remove your own admin role.');
        }
    }

    private function present(User $user): array
    {
        return $user->only(['id', 'name', 'email', 'role', 'is_active', 'created_at']);
    }
}
