<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    // List all users
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    // Approve user account
    public function approve($id)
    {
        $user = User::findOrFail($id);
        $user->approved = true;
        $user->save();
        return response()->json(['message' => 'User approved successfully']);
    }

    // Activate or deactivate user
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->active = !$user->active;
        $user->save();
        return response()->json(['message' => 'User status updated']);
    }

    // Update role (admin/user)
    public function updateRole(Request $request, $id)
    {
        $request->validate(['role' => 'required|in:superuser,admin,user']);
        $user = User::findOrFail($id);
        $user->role = $request->role;
        $user->save();
        return response()->json(['message' => 'Role updated successfully']);
    }

    // Delete user
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }
}
