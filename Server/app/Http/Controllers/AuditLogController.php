<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuditLog;

class AuditLogController extends Controller
{
    // List/filter audit logs for super user
    public function index(Request $request)
    {
        $query = AuditLog::with(['business', 'user']);
        if ($request->has('business')) {
            $query->whereHas('business', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->business . '%');
            });
        }
        if ($request->has('user')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->user . '%');
            });
        }
        if ($request->has('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }
        $logs = $query->orderByDesc('created_at')->limit(200)->get()->map(function($log) {
            return [
                'id' => $log->id,
                'business_name' => $log->business->name ?? '',
                'user_name' => $log->user->name ?? '',
                'action' => $log->action,
                'details' => $log->details,
                'created_at' => $log->created_at,
            ];
        });
        return response()->json($logs);
    }
}
