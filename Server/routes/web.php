<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\CompanyController;

//superuser


//company 

Route::post('/register-company', [CompanyController::class, 'registerCompany']);
Route::get('/companies/pending', [CompanyController::class, 'index']);
Route::put('/companies/{id}/approve', [CompanyController::class, 'approve']);
Route::delete('/companies/{id}/reject', [CompanyController::class, 'reject']);



// AUTHENTICATION & USER MANAGEMENT
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Change password for authenticated users (supports forced first-login change)
Route::middleware('auth:sanctum')->post('/change-password', [AuthController::class, 'changePassword']);

Route::post('/verify-user/{id}', [AuthController::class, 'verifyUser']); // Superuser only
Route::get('/unverified-users', [AuthController::class, 'unverifiedUsers']); // Superuser only
// SUPER USER: Company management
Route::get('/companies', [AuthController::class, 'listCompanies']);
Route::patch('/companies/{id}/activate', [AuthController::class, 'activateCompany']);
Route::patch('/companies/{id}/deactivate', [AuthController::class, 'deactivateCompany']);

// SUPERUSER API ROUTES
Route::prefix('api/superuser')->group(function () {
  Route::get('/businesses', function () {
    return response()->json(['businesses' => \App\Models\Company::all()]);
  });
  Route::get('/companies', function () {
    return response()->json(['companies' => \App\Models\Company::where('active', false)->get()]);
  });
  Route::get('/companies', function () {
    $status = request('status');
    $query = \App\Models\Company::query();
    if ($status === 'pending') {
      $query->where('active', false);
    }
    return response()->json(['companies' => $query->get()]);
  });
  Route::get('/users', function () {
    return response()->json(['users' => \App\Models\User::with('role')->get()]);
  });
  Route::get('/audit-logs', function () {
    return response()->json(['logs' => []]);
  });
  Route::get('/business-categories', function () {
    return response()->json(['categories' => \App\Models\BusinessCategory::all()]);
  });
  Route::get('/feature-toggles', function () {
    return response()->json(['features' => []]);
  });
  Route::get('/announcement', function () {
    return response()->json(['announcement' => null]);
  });
  Route::get('/support-tickets', function () {
    return response()->json(['tickets' => []]);
  });
  Route::get('/subscriptions', function () {
    return response()->json(['subscriptions' => []]);
  });
});

// --- Superuser API (aggregates, users, audit, settings, support, exports, subscriptions, impersonation) ---
Route::prefix('api/super')->middleware(['auth:sanctum'])->group(function () {
    // Dashboard aggregates & analytics (cached)
    Route::get('/dashboard/overview', [\App\Http\Controllers\SuperUser\DashboardController::class, 'overview']);

    // User management (CRUD, search, paginate)
    Route::get('/users', [\App\Http\Controllers\SuperUser\UserController::class, 'index']);
    Route::post('/users', [\App\Http\Controllers\SuperUser\UserController::class, 'store']);
    Route::get('/users/{id}', [\App\Http\Controllers\SuperUser\UserController::class, 'show']);
    Route::put('/users/{id}', [\App\Http\Controllers\SuperUser\UserController::class, 'update']);
    Route::patch('/users/{id}/activate', [\App\Http\Controllers\SuperUser\UserController::class, 'activate']);
    Route::patch('/users/{id}/deactivate', [\App\Http\Controllers\SuperUser\UserController::class, 'deactivate']);
    Route::post('/users/{id}/reset-password', [\App\Http\Controllers\SuperUser\UserController::class, 'resetPassword']);

    // Audit logs (filterable)
    Route::get('/audit-logs', [\AppHttp\Controllers\SuperUser\AuditLogController::class, 'index']);

    // System settings
    Route::get('/settings', [\AppHttp\Controllers\SuperUser\SystemSettingController::class, 'index']);
    Route::post('/settings', [\AppHttp\Controllers\SuperUser\SystemSettingController::class, 'store']);
    Route::put('/settings/{id}', [\AppHttp\Controllers\SuperUser\SystemSettingController::class, 'update']);
    Route::delete('/settings/{id}', [\AppHttp\Controllers\SuperUser\SystemSettingController::class, 'destroy']);

    // Support tickets
    Route::get('/support-tickets', [\AppHttp\Controllers\SuperUser\SupportTicketController::class, 'index']);
    Route::post('/support-tickets/{id}/reply', [\AppHttp\Controllers\SuperUser\SupportTicketController::class, 'reply']);
    Route::patch('/support-tickets/{id}/resolve', [\AppHttp\Controllers\SuperUser\SupportTicketController::class, 'resolve']);

    // Exports (enqueue export job, return job id / URL)
    Route::post('/exports', [\AppHttp\Controllers\SuperUser\ExportController::class, 'store']);
    Route::get('/exports', [\AppHttp\Controllers\SuperUser\ExportController::class, 'index']);
    Route::get('/exports/{id}', [\AppHttp\Controllers\SuperUser\ExportController::class, 'show']);
    Route::get('/exports/{id}/download', [\AppHttp\Controllers\SuperUser\ExportController::class, 'download']);

    // Subscriptions & billing management
    Route::get('/subscriptions', [\AppHttp\Controllers\SuperUser\SubscriptionController::class, 'index']);
    Route::get('/subscriptions/{id}', [\AppHttp\Controllers\SuperUser\SubscriptionController::class, 'show']);
    Route::patch('/subscriptions/{id}/activate', [\AppHttp\Controllers\SuperUser\SubscriptionController::class, 'activate']);
    Route::patch('/subscriptions/{id}/deactivate', [\AppHttp\Controllers\SuperUser\SubscriptionController::class, 'deactivate']);
    Route::post('/subscriptions/{id}/renew', [\AppHttp\Controllers\SuperUser\SubscriptionController::class, 'renew']);
    Route::post('/subscriptions/{id}/trial', [\AppHttp\Controllers\SuperUser\SubscriptionController::class, 'assignTrial']);
    Route::get('/subscriptions/{id}/transactions', [\AppHttp\Controllers\SuperUser\SubscriptionController::class, 'transactions']);

    // Plan management
    Route::get('/plans', [\AppHttp\Controllers\SuperUser\SubscriptionController::class, 'listPlans']);
    Route::post('/plans', [\AppHttp\Controllers\SuperUser\SubscriptionController::class, 'storePlan']);
    Route::put('/plans/{id}', [\AppHttp\Controllers\SuperUser\SubscriptionController::class, 'updatePlan']);
    Route::delete('/plans/{id}', [\AppHttp\Controllers\SuperUser\SubscriptionController::class, 'deletePlan']);

    // Impersonation
    Route::post('/impersonate/{userId}', [\AppHttp\Controllers\SuperUser\ImpersonationController::class, 'impersonate']);
    Route::post('/impersonate/revert', [\AppHttp\Controllers\SuperUser\ImpersonationController::class, 'revert']);
    Route::get('/impersonate/status', [\AppHttp\Controllers\SuperUser\ImpersonationController::class, 'status']);
    Route::get('/impersonate/businesses', [\AppHttp\Controllers\SuperUser\ImpersonationController::class, 'listBusinesses']);

    // Chat management
    Route::get('/chats', [\AppHttp\Controllers\SuperUser\ChatController::class, 'index']);
    Route::post('/chats', [\AppHttp\Controllers\SuperUser\ChatController::class, 'store']);
    Route::get('/chats/{id}', [\AppHttp\Controllers\SuperUser\ChatController::class, 'show']);
    Route::post('/chats/{id}/messages', [\AppHttp\Controllers\SuperUser\ChatController::class, 'sendMessage']);
    Route::patch('/chats/{id}/close', [\AppHttp\Controllers\SuperUser\ChatController::class, 'closeChat']);
    Route::get('/chats/unread-count', [\AppHttp\Controllers\SuperUser\ChatController::class, 'unreadCount']);
});

// PRODUCTS
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/statistics', [ProductController::class, 'getStatistics']);
Route::get('/products/out-of-stock', [ProductController::class, 'getOutOfStockProducts']);
Route::get('/products/low-stock', [ProductController::class, 'getLowStockProducts']);
Route::get('/products/csv-template', [ProductController::class, 'downloadCSVTemplate']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::middleware(['role:admin'])->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
    Route::post('/products/bulk', [ProductController::class, 'storeBulk']);
    Route::post('/products/import-csv', [ProductController::class, 'importCSV']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    // Business Categories (admin only)
    Route::post('/business-categories', [ProductController::class, 'storeCategory']);
    Route::put('/business-categories/{id}', [ProductController::class, 'updateCategory']);
    Route::delete('/business-categories/{id}', [ProductController::class, 'destroyCategory']);

    // Warehouses (admin only)
    Route::post('/warehouses', [ProductController::class, 'storeWarehouse']);
    Route::put('/warehouses/{id}', [ProductController::class, 'updateWarehouse']);
    Route::delete('/warehouses/{id}', [ProductController::class, 'destroyWarehouse']);
});

// SALES
Route::post('/sales', [SaleController::class, 'store']);
Route::get('/sales', [SaleController::class, 'index']);
Route::get('/sales/{id}', [SaleController::class, 'show']);

// DASHBOARD
Route::get('/dashboard-stats', [DashboardController::class, 'getStats']);

// PURCHASES
Route::post('/purchases', [PurchaseController::class, 'store']);
Route::get('/purchases', [PurchaseController::class, 'index']);

// REPORTS
Route::prefix('reports')->group(function () {
    Route::get('/sales-overview', [ReportController::class, 'salesOverview']);
    Route::get('/inventory-summary', [ReportController::class, 'inventorySummary']);
    Route::get('/top-selling-products', [ReportController::class, 'topSellingProducts']);
});

// EXPENSES
Route::get('/expenses', [ExpenseController::class, 'index']);
Route::get('/expenses/categories', [ExpenseController::class, 'getCategories']);
Route::get('/expenses/payment-methods', [ExpenseController::class, 'getPaymentMethods']);
Route::get('/expenses/dashboard', [ExpenseController::class, 'getDashboard']);
Route::get('/expenses/profit-loss', [ExpenseController::class, 'getProfitLoss']);
Route::get('/expenses/export', [ExpenseController::class, 'export']);
Route::get('/expenses/{id}', [ExpenseController::class, 'show']);
Route::post('/expenses', [ExpenseController::class, 'store']);
Route::put('/expenses/{id}', [ExpenseController::class, 'update']);
Route::delete('/expenses/{id}', [ExpenseController::class, 'destroy']);
Route::patch('/expenses/{id}/approve', [ExpenseController::class, 'approve']);
Route::patch('/expenses/{id}/reject', [ExpenseController::class, 'reject']);
Route::patch('/expenses/{id}/mark-paid', [ExpenseController::class, 'markAsPaid']);

// Test route for expense system
Route::get('/test-expenses', function () {
    try {
        $expenses = App\Models\Expense::all();
        return response()->json([
            'status' => 'success',
            'count' => $expenses->count(),
            'expenses' => $expenses
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
});

// Roles & Categories endpoints (public, no auth required)
Route::get('/roles', [\App\Http\Controllers\RoleController::class, 'index']);
Route::get('/business-categories', [\App\Http\Controllers\BusinessCategoryController::class, 'index']);

// API-compatible company endpoints used by frontend (alias to existing controller methods)
Route::prefix('api')->group(function () {
    // Roles endpoint (public)
    Route::get('/roles', [\App\Http\Controllers\RoleController::class, 'index']);

    // Business categories endpoint (public)
    Route::get('/business-categories', [\App\Http\Controllers\BusinessCategoryController::class, 'index']);

    // list all companies
    Route::get('/companies', function () {
        return response()->json(['companies' => \App\Models\Company::all()]);
    });

    // pending companies
    Route::get('/companies/pending', function () {
        return response()->json(['companies' => \App\Models\Company::where('active', false)->get()]);
    });

    // approve / reject via API (map to existing controller methods)
    Route::post('/companies/{id}/approve', [CompanyController::class, 'approve']);
    Route::post('/companies/{id}/reject', [CompanyController::class, 'reject']);

    // activate / deactivate aliases (map to existing AuthController methods)
    Route::patch('/companies/{id}/activate', [AuthController::class, 'activateCompany']);
    Route::patch('/companies/{id}/deactivate', [AuthController::class, 'deactivateCompany']);
});

// Dashboard alias for legacy frontend calls (protected)
Route::middleware('auth:sanctum')->get('/dashboard/overview', [\App\Http\Controllers\SuperUser\DashboardController::class, 'overview']);

// ADMIN API ENDPOINTS (for admin pages)
Route::middleware(['auth:sanctum'])->group(function () {
    // Users CRUD for admin
    Route::get('/users', function () {
        // Only return users for the admin's company
        $user = auth()->user();
        if (!$user || !in_array(strtolower($user->role->name), ['admin','administrator'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $users = \App\Models\User::with('role')->where('company_id', $user->company_id)->get();
        return response()->json(['users' => $users]);
    });

    Route::post('/users', function (\Illuminate\Http\Request $request) {
        $user = auth()->user();
        if (!$user || !in_array(strtolower($user->role->name), ['admin','administrator'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $payload = $request->validate([
            'name'=>'required|string|max:255',
            'email'=>'required|email|unique:users,email',
            'password'=>'required|string|min:6',
            'role_id'=>'required|exists:roles,id'
        ]);
        $payload['company_id'] = $user->company_id;
        $payload['password'] = \Illuminate\Support\Facades\Hash::make($payload['password']);
        $payload['verified'] = true;
        $payload['must_change_password'] = true;
        $newUser = \App\Models\User::create($payload);
        return response()->json($newUser, 201);
    });

    Route::put('/users/{id}', function ($id, \Illuminate\Http\Request $request) {
        $user = auth()->user();
        $target = \App\Models\User::findOrFail($id);
        if (!$user || !in_array(strtolower($user->role->name), ['admin','administrator']) || $target->company_id !== $user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $data = $request->validate([
            'name'=>'nullable|string|max:255',
            'email'=>['nullable','email', \Illuminate\Validation\Rule::unique('users','email')->ignore($id)],
            'role_id'=>'nullable|exists:roles,id'
        ]);
        $target->update($data);
        return response()->json($target);
    });

    Route::delete('/users/{id}', function ($id) {
        $user = auth()->user();
        $target = \App\Models\User::findOrFail($id);
        if (!$user || !in_array(strtolower($user->role->name), ['admin','administrator']) || $target->company_id !== $user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $target->delete();
        return response()->json(['message' => 'User deleted']);
    });

    // Warehouses CRUD for admin
    Route::get('/warehouses', function () {
        $user = auth()->user();
        if (!$user || !in_array(strtolower($user->role->name), ['admin','administrator'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $warehouses = \App\Models\Warehouse::where('company_id', $user->company_id)->get();
        return response()->json(['warehouses' => $warehouses]);
    });

    Route::post('/warehouses', function (\Illuminate\Http\Request $request) {
        $user = auth()->user();
        if (!$user || !in_array(strtolower($user->role->name), ['admin','administrator'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $payload = $request->validate([
            'name'=>'required|string|max:255',
            'type'=>'nullable|string|max:50'
        ]);
        $payload['company_id'] = $user->company_id;
        $warehouse = \App\Models\Warehouse::create($payload);
        return response()->json($warehouse, 201);
    });

    Route::put('/warehouses/{id}', function ($id, \Illuminate\Http\Request $request) {
        $user = auth()->user();
        $warehouse = \App\Models\Warehouse::findOrFail($id);
        if (!$user || !in_array(strtolower($user->role->name), ['admin','administrator']) || $warehouse->company_id !== $user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $data = $request->validate([
            'name'=>'nullable|string|max:255',
            'type'=>'nullable|string|max:50'
        ]);
        $warehouse->update($data);
        return response()->json($warehouse);
    });

    Route::delete('/warehouses/{id}', function ($id) {
        $user = auth()->user();
        $warehouse = \App\Models\Warehouse::findOrFail($id);
        if (!$user || !in_array(strtolower($user->role->name), ['admin','administrator']) || $warehouse->company_id !== $user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $warehouse->delete();
        return response()->json(['message' => 'Warehouse deleted']);
    });
});
