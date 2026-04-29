<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuditLog;
use Illuminate\Validation\Rule;

class AuditLogController extends Controller
{
    // List/filter audit logs for super user
    public function index(Request $request)
    {
        $query = AuditLog::with(['business', 'user', 'reviewer']);
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
        $perPage = max(1, min((int) $request->integer('per_page', 25), 100));
        $logs = $query->orderByDesc('created_at')->paginate($perPage)->through(function($log) {
            return [
                'id' => $log->id,
                'business_name' => $log->business->name ?? '',
                'user_name' => $log->user->name ?? '',
                'action' => $log->action,
                'auditable_type' => $log->auditable_type,
                'auditable_id' => $log->auditable_id,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'created_at' => $log->created_at,
                'review_status' => $log->review_status,
                'notes' => $log->notes,
                'reviewed_at' => $log->reviewed_at,
                'reviewed_by' => $log->reviewer?->name,
            ];
        });
        return response()->json($logs);
    }

    public function adminIndex(Request $request)
    {
        $user = $request->user();
        if (! $this->isAuditManager($user)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (! $user || ! $user->company_id) {
            return response()->json(['message' => 'No company context found.'], 403);
        }

        $perPage = max(1, min((int) $request->integer('per_page', 20), 100));

        $query = AuditLog::query()
            ->with(['user:id,name,email', 'reviewer:id,name'])
            ->where('company_id', $user->company_id);

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        if ($request->filled('review_status')) {
            $query->where('review_status', $request->string('review_status'));
        }

        if ($request->filled('model')) {
            $modelName = strtolower(trim((string) $request->input('model')));
            $query->whereRaw('LOWER(auditable_type) LIKE ?', ['%' . $modelName]);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', '%' . $search . '%')
                    ->orWhere('user_name', 'like', '%' . $search . '%')
                    ->orWhere('action', 'like', '%' . $search . '%')
                    ->orWhere('auditable_type', 'like', '%' . $search . '%')
                    ->orWhere('auditable_id', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        $paginator = $query
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->through(function (AuditLog $log) {
                return $this->serializeForAdmin($log);
            });

        $summaryBase = AuditLog::query()->where('company_id', $user->company_id);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'summary' => [
                'total_logs' => (clone $summaryBase)->count(),
                'open_reviews' => (clone $summaryBase)->where('review_status', AuditLog::REVIEW_OPEN)->count(),
                'in_progress_reviews' => (clone $summaryBase)->where('review_status', AuditLog::REVIEW_IN_PROGRESS)->count(),
                'resolved_reviews' => (clone $summaryBase)->where('review_status', AuditLog::REVIEW_RESOLVED)->count(),
                'transaction_logs' => (clone $summaryBase)
                    ->where(function ($q) {
                        $q->where('auditable_type', 'like', '%\\Sale')
                            ->orWhere('auditable_type', 'like', '%\\Invoice')
                            ->orWhere('auditable_type', 'like', '%\\Expense')
                            ->orWhere('auditable_type', 'like', '%\\CreditTransaction');
                    })
                    ->count(),
            ],
        ]);
    }

    public function adminShow(Request $request, int $id)
    {
        if (! $this->isAuditManager($request->user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $log = $this->resolveCompanyLogOrFail($request, $id);
        return response()->json(['data' => $this->serializeForAdmin($log)]);
    }

    public function adminUpdate(Request $request, int $id)
    {
        if (! $this->isAuditManager($request->user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $log = $this->resolveCompanyLogOrFail($request, $id);
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
            'review_status' => ['nullable', 'string', Rule::in(AuditLog::allowedReviewStatuses())],
        ]);

        if (array_key_exists('notes', $validated)) {
            $log->notes = $validated['notes'];
        }

        if (array_key_exists('review_status', $validated)) {
            $log->review_status = $validated['review_status'];
            if ($validated['review_status'] === AuditLog::REVIEW_RESOLVED) {
                $log->reviewed_at = now();
                $log->reviewed_by = $request->user()?->id;
            } else {
                $log->reviewed_at = null;
                $log->reviewed_by = null;
            }
        }

        $log->save();
        $log->load(['user:id,name,email', 'reviewer:id,name']);

        return response()->json([
            'message' => 'Audit entry updated successfully.',
            'data' => $this->serializeForAdmin($log),
        ]);
    }

    public function adminDestroy(Request $request, int $id)
    {
        if (! $this->isAuditManager($request->user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $log = $this->resolveCompanyLogOrFail($request, $id);
        $log->delete();

        return response()->json(['message' => 'Audit entry deleted successfully.']);
    }

    public function adminBulkDestroy(Request $request)
    {
        $user = $request->user();
        if (! $this->isAuditManager($user)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer'],
        ]);

        $deleted = AuditLog::query()
            ->where('company_id', $user?->company_id)
            ->whereIn('id', $validated['ids'])
            ->delete();

        return response()->json([
            'message' => 'Audit entries deleted successfully.',
            'deleted' => $deleted,
        ]);
    }

    private function resolveCompanyLogOrFail(Request $request, int $id): AuditLog
    {
        $user = $request->user();

        return AuditLog::query()
            ->with(['user:id,name,email', 'reviewer:id,name'])
            ->where('company_id', $user?->company_id)
            ->findOrFail($id);
    }

    private function serializeForAdmin(AuditLog $log): array
    {
        return [
            'id' => $log->id,
            'company_id' => $log->company_id,
            'action' => $log->action,
            'auditable_type' => $log->auditable_type,
            'auditable_id' => $log->auditable_id,
            'user_id' => $log->user_id,
            'user_name' => $log->user_name ?: $log->user?->name,
            'user_email' => $log->user?->email,
            'ip_address' => $log->ip_address,
            'old_values' => $log->old_values,
            'new_values' => $log->new_values,
            'notes' => $log->notes,
            'review_status' => $log->review_status,
            'reviewed_at' => $log->reviewed_at,
            'reviewed_by' => $log->reviewer?->name,
            'created_at' => $log->created_at,
            'updated_at' => $log->updated_at,
        ];
    }

    private function isAuditManager($user): bool
    {
        if (! $user) {
            return false;
        }

        $role = strtolower($user->role->name ?? '');
        return in_array($role, ['admin', 'superuser'], true);
    }
}
