<?php
namespace App\Http\Controllers\SuperUser;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\AuditLog;

class ImpersonationController extends Controller
{
    // POST /api/super/impersonate/{userId} - start impersonation
    public function impersonate(Request $request, $userId)
    {
        // Only superusers can impersonate
        if (auth()->user()->role->name !== 'superuser') {
            return response()->json(['error' => 'Only superusers can impersonate users'], 403);
        }

        $targetUser = User::findOrFail($userId);

        // Cannot impersonate superusers
        if ($targetUser->role->name === 'superuser') {
            return response()->json(['error' => 'Cannot impersonate other superusers'], 403);
        }

        // Cannot impersonate inactive users
        if (!$targetUser->verified) {
            return response()->json(['error' => 'Cannot impersonate unverified users'], 403);
        }

        $superUserId = auth()->id();
        $impersonationToken = \Illuminate\Support\Str::random(40);
        $expiresAt = now()->addMinutes(30);

        // Store impersonation record in cache
        Cache::put(
            "impersonate:{$impersonationToken}",
            [
                'target_user_id' => $targetUser->id,
                'original_user_id' => $superUserId,
                'started_at' => now(),
                'expires_at' => $expiresAt
            ],
            $expiresAt
        );

        // Create impersonation token for API
        $apiToken = $targetUser->createToken('impersonate-' . $impersonationToken)->plainTextToken;

        // Log activity
        AuditLog::create([
            'action' => 'impersonate_start',
            'user_id' => $superUserId,
            'user_name' => auth()->user()->name,
            'auditable_type' => User::class,
            'auditable_id' => $targetUser->id,
            'ip_address' => $request->ip(),
            'notes' => "Started impersonating {$targetUser->name} ({$targetUser->email})"
        ]);

        return response()->json([
            'impersonation_token' => $impersonationToken,
            'api_token' => $apiToken,
            'target_user' => $targetUser->load('role', 'company'),
            'expires_at' => $expiresAt,
            'message' => "Now impersonating {$targetUser->name}"
        ]);
    }

    // POST /api/super/impersonate/revert - stop impersonation
    public function revert(Request $request)
    {
        $impersonationToken = $request->input('impersonation_token');

        if (!$impersonationToken) {
            return response()->json(['error' => 'No impersonation token provided'], 400);
        }

        $impersonationData = Cache::get("impersonate:{$impersonationToken}");

        if (!$impersonationData) {
            return response()->json(['error' => 'Invalid or expired impersonation token'], 400);
        }

        $originalUserId = $impersonationData['original_user_id'];
        $targetUserId = $impersonationData['target_user_id'];

        // Log activity
        AuditLog::create([
            'action' => 'impersonate_revert',
            'user_id' => $originalUserId,
            'user_name' => User::find($originalUserId)->name ?? 'Unknown',
            'auditable_type' => User::class,
            'auditable_id' => $targetUserId,
            'ip_address' => $request->ip(),
            'notes' => "Reverted from impersonating user ID {$targetUserId}"
        ]);

        // Invalidate the token
        Cache::forget("impersonate:{$impersonationToken}");

        return response()->json(['message' => 'Impersonation ended']);
    }

    // GET /api/super/impersonate/status - check current impersonation status
    public function status(Request $request)
    {
        $impersonationToken = $request->input('impersonation_token');

        if (!$impersonationToken) {
            return response()->json(['impersonating' => false]);
        }

        $impersonationData = Cache::get("impersonate:{$impersonationToken}");

        if (!$impersonationData) {
            return response()->json(['impersonating' => false]);
        }

        $targetUser = User::find($impersonationData['target_user_id']);
        $originalUser = User::find($impersonationData['original_user_id']);

        return response()->json([
            'impersonating' => true,
            'target_user' => $targetUser->load('role', 'company'),
            'original_user' => $originalUser->load('role'),
            'started_at' => $impersonationData['started_at'],
            'expires_at' => $impersonationData['expires_at'],
            'remaining_minutes' => now()->diffInMinutes($impersonationData['expires_at'], false)
        ]);
    }

    // GET /api/super/impersonate/businesses - list all businesses (for dropdown)
    public function listBusinesses()
    {
        $businesses = Company::where('active', true)
            ->with(['users' => function ($q) {
                $q->where('verified', true)->whereHas('role', fn($r) => $r->where('name', '!=', 'superuser'));
            }])
            ->get();

        return response()->json(['businesses' => $businesses]);
    }
}
