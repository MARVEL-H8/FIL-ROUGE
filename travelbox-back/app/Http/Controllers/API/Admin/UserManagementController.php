<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserManagementController extends Controller
{
    /**
     * Get all users
     */
    public function getAllUsers(Request $request)
    {
        // Vérifier manuellement si l'utilisateur est un admin
        if (!$request->user() || $request->user()->username !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }
        $query = User::query();

        if ($request->has('active')) {
            $query->where('active', $request->active);
        }

        $users = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Get all active users based on tokens
     */
    public function getActiveUsers(Request $request)
    {
        // Vérifier manuellement si l'utilisateur est un admin
        if (!$request->user() || $request->user()->username !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }
        $activeUsers = User::whereHas('tokens', function ($query) {
            $query->where('created_at', '>=', now()->subHours(1));
        })->get();

        return response()->json([
            'success' => true,
            'data' => $activeUsers,
        ]);
    }

    /**
     * Toggle user active status
     */
    public function toggleUserStatus(Request $request, $userId)
    {
        // Vérifier manuellement si l'utilisateur est un admin
        if (!$request->user() || $request->user()->username !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }
        $user = User::findOrFail($userId);
        $user->active = !$user->active;
        $user->save();

        if (!$user->active) {
            $user->tokens()->delete();
        }

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => $user->active ? 'User activated' : 'User deactivated',
        ]);
    }

    /**
     * Create a new user
     */
    public function createUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'nullable|in:traveler,sender,both',
            'active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'active' => $request->active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'User created successfully',
        ], 201);
    }
}
