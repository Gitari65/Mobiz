<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Company;

class SuperUserController extends Controller
{
    // List all users with business and role info
    public function users(Request $request)
    {
        $query = User::with(['company', 'role']);
        if ($request->has('business')) {
            $query->whereHas('company', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->business . '%');
            });
        }
        $users = $query->get()->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'business_name' => $user->company->name ?? '',
                'role' => $user->role->name ?? '',
                'status' => $user->status ?? 'active',
            ];
        });
        return response()->json($users);
    }

    // Activate user
    public function activateUser($id)
    {
        $user = User::findOrFail($id);
        $user->status = 'active';
        $user->save();
        return response()->json(['success' => true]);
    }

    // Deactivate user
    public function deactivateUser($id)
    {
        $user = User::findOrFail($id);
        $user->status = 'inactive';
        $user->save();
        return response()->json(['success' => true]);
    }
}
