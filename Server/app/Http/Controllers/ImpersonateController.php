<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\User;

class ImpersonateController extends Controller
{
    // List all businesses and their admin
    public function businesses()
    {
        $businesses = Company::with(['admin'])->get()->map(function($b) {
            return [
                'id' => $b->id,
                'name' => $b->name,
                'admin_name' => $b->admin->name ?? '',
                'admin_email' => $b->admin->email ?? '',
            ];
        });
        return response()->json($businesses);
    }

    // Impersonate a business admin (set session/token)
    public function impersonate($businessId)
    {
        $company = Company::findOrFail($businessId);
        $admin = $company->admin;
        if (!$admin) {
            return response()->json(['error' => 'No admin found for this business'], 404);
        }
        // For demo: set user_id in session (in real app, set auth token/cookie)
        session(['impersonate_user_id' => $admin->id]);
        return response()->json(['success' => true]);
    }
}
