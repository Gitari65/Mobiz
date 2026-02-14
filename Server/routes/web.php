<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TaxConfigurationController;

//superuser


//company 

Route::post('/register-company', [CompanyController::class, 'registerCompany']);
Route::get('/companies/pending', [CompanyController::class, 'index']);
Route::put('/companies/{id}/approve', [CompanyController::class, 'approve']);
Route::delete('/companies/{id}/reject', [CompanyController::class, 'reject']);





// AUTHENTICATION & USER MANAGEMENT
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/verify-otp', [AuthController::class, 'verifyLoginOtp']);
Route::post('/login/resend-otp', [AuthController::class, 'resendLoginOtp']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyResetOtp']);

// Change password for authenticated users (supports forced first-login change)
Route::middleware('auth:sanctum')->post('/change-password', [AuthController::class, 'changePassword']);

// Profile Management Routes (Authenticated Users)
Route::middleware('auth:sanctum')->prefix('api/profile')->group(function () {
    Route::get('/', [ProfileController::class, 'show']);
    Route::put('/update', [ProfileController::class, 'update']);
    Route::post('/upload-picture', [ProfileController::class, 'uploadPicture']);
    Route::delete('/remove-picture', [ProfileController::class, 'removePicture']);
    Route::put('/change-password', [ProfileController::class, 'changePassword']);
    Route::post('/request-password-reset', [ProfileController::class, 'requestPasswordReset']);
});

// Password Reset via Token (Public route)
Route::post('/api/password/reset-with-token', [ProfileController::class, 'resetPasswordWithToken']);

// Company Profile Management Routes (Authenticated Admin/Superuser)
Route::middleware('auth:sanctum')->prefix('api/company')->group(function () {
    Route::get('/me', [CompanyController::class, 'myCompany']);
    Route::put('/me', [CompanyController::class, 'updateMyCompany']);
    Route::get('/subscription', [CompanyController::class, 'mySubscription']);
});

// Companies List Route (for SuperUser to get all companies)
Route::middleware('auth:sanctum')->get('/api/companies', [CompanyController::class, 'getAllCompanies']);

// Product Empties/Returnables (API exposure here to ensure route availability)
Route::prefix('api')->middleware('api')->group(function () {
    Route::get('products/{product}/empties', [ProductController::class, 'getEmpties']);
    Route::get('products/{product}/available-empties', [ProductController::class, 'getAvailableEmpties']);
    Route::post('products/{product}/empties', [ProductController::class, 'linkEmpty']);
    Route::put('products/{product}/empties/{empty}', [ProductController::class, 'updateEmpty']);
    Route::delete('products/{product}/empties/{empty}', [ProductController::class, 'unlinkEmpty']);
});

// Settings Management Routes
Route::middleware('auth:sanctum')->prefix('api/settings')->group(function () {
    // User Settings (All authenticated users - Cashier, Admin, SuperUser)
    Route::get('/user', [\App\Http\Controllers\UserSettingController::class, 'show']);
    Route::put('/user', [\App\Http\Controllers\UserSettingController::class, 'update']);
    
    // Company Settings (Admin and SuperUser only)
    Route::get('/company', [\App\Http\Controllers\CompanySettingController::class, 'show']);
    Route::put('/company', [\App\Http\Controllers\CompanySettingController::class, 'update']);
    Route::post('/company/upload-logo', [\App\Http\Controllers\CompanySettingController::class, 'uploadLogo']);
    Route::delete('/company/remove-logo', [\App\Http\Controllers\CompanySettingController::class, 'removeLogo']);
    
    // System Settings (SuperUser only)
    Route::get('/system', [\App\Http\Controllers\SystemSettingController::class, 'index']);
    Route::get('/system/{key}', [\App\Http\Controllers\SystemSettingController::class, 'show']);
    Route::post('/system', [\App\Http\Controllers\SystemSettingController::class, 'store']);
    Route::put('/system/{id}', [\App\Http\Controllers\SystemSettingController::class, 'update']);
    Route::delete('/system/{id}', [\App\Http\Controllers\SystemSettingController::class, 'destroy']);
    Route::post('/system/bulk-update', [\App\Http\Controllers\SystemSettingController::class, 'bulkUpdate']);
    
    // Feature Toggles (SuperUser only)
    Route::get('/features', [\App\Http\Controllers\FeatureToggleController::class, 'index']);
    Route::get('/features/available', [\App\Http\Controllers\FeatureToggleController::class, 'available']);
    Route::get('/features/{featureKey}/status', [\App\Http\Controllers\FeatureToggleController::class, 'isEnabled']);
    Route::post('/features/toggle', [\App\Http\Controllers\FeatureToggleController::class, 'toggle']);
    Route::post('/features/bulk-update', [\App\Http\Controllers\FeatureToggleController::class, 'bulkUpdate']);
});

// Public Settings (accessible without authentication)
Route::get('/api/settings/public', [\App\Http\Controllers\SystemSettingController::class, 'getPublicSettings']);

// Test endpoint to create a user with known temporary password (DEV ONLY)
Route::get('/test/create-user', function () {
    $tempPassword = 'TESTPASS123';
    
    $user = \App\Models\User::updateOrCreate(
        ['email' => 'test@example.com'],
        [
            'name' => 'Test User',
            'password' => \Illuminate\Support\Facades\Hash::make($tempPassword),
            'role_id' => \App\Models\Role::where('name', 'admin')->first()?->id ?? 1,
            'company_id' => \App\Models\Company::first()?->id ?? 1,
            'verified' => true,
            'must_change_password' => true,
        ]
    );

    return response()->json([
        'message' => 'Test user ready.',
        'email' => 'test@example.com',
        'temporary_password' => $tempPassword,
        'user' => $user->only(['id', 'name', 'email', 'verified', 'must_change_password'])
    ]);
});

// Test endpoint to verify password (DEV ONLY)
Route::post('/test/verify-password', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'email' => 'required|string|email',
        'password' => 'required|string'
    ]);

    $user = \App\Models\User::where('email', $request->email)->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    $match = \Illuminate\Support\Facades\Hash::check($request->password, $user->password);
    return response()->json([
        'email' => $request->email,
        'password_provided' => $request->password,
        'password_hash' => $user->password,
        'match' => $match,
        'user_verified' => $user->verified,
        'user_must_change_password' => $user->must_change_password
    ]);
});

// Test endpoint to reset a user's password (DEV ONLY)
Route::post('/test/reset-password/{email}', function ($email) {
    $user = \App\Models\User::where('email', $email)->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    $tempPassword = strtoupper(\Illuminate\Support\Str::random(8));
    $user->password = \Illuminate\Support\Facades\Hash::make($tempPassword);
    $user->must_change_password = true;
    $user->save();

    return response()->json([
        'message' => 'Password reset. Use the temporary password below to login.',
        'email' => $email,
        'temporary_password' => $tempPassword,
        'note' => 'In production, this password would be sent via email. You must change it on first login.'
    ]);
});

// Test endpoint to get cached OTP (DEV ONLY)
Route::get('/test/get-otp/{email}', function ($email) {
    $user = \App\Models\User::where('email', $email)->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    $cacheKey = 'login_otp_' . $user->id;
    $otp = \Illuminate\Support\Facades\Cache::get($cacheKey);

    if (!$otp) {
        return response()->json(['error' => 'No OTP cached for this user'], 404);
    }

    return response()->json([
        'email' => $email,
        'user_id' => $user->id,
        'otp' => $otp,
        'note' => 'This OTP endpoint is for testing only. Remove before production.'
    ]);
});

Route::post('/verify-user/{id}', [AuthController::class, 'verifyUser']); // Superuser only
Route::post('/reset-otp-limit/{userId}', [AuthController::class, 'resetOtpLimit']); // Superuser only
Route::get('/unverified-users', [AuthController::class, 'unverifiedUsers']); // Superuser only
// SUPER USER: Company management
Route::get('/companies', [AuthController::class, 'listCompanies']);
Route::patch('/companies/{id}/activate', [AuthController::class, 'activateCompany']);
Route::patch('/companies/{id}/deactivate', [AuthController::class, 'deactivateCompany']);

//Reports
// Route::middleware('auth:sanctum')->prefix('/reports')->group(function () {
//     Route::get('/sales', [ReportController::class, 'salesReport']);
//     Route::get('/inventory', [ReportController::class, 'inventoryReport']);
//     Route::get('/transfers', [ReportController::class, 'transferReport']);
//     Route::get('analytics/overview', [DashboardController::class, 'analyticsOverview']);
// });


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
    Route::get('/audit-logs', [\App\Http\Controllers\SuperUser\AuditLogController::class, 'index']);

    // System settings
    Route::get('/settings', [\App\Http\Controllers\SuperUser\SystemSettingController::class, 'index']);
    Route::post('/settings', [\App\Http\Controllers\SuperUser\SystemSettingController::class, 'store']);
    Route::put('/settings/{id}', [\App\Http\Controllers\SuperUser\SystemSettingController::class, 'update']);
    Route::delete('/settings/{id}', [\App\Http\Controllers\SuperUser\SystemSettingController::class, 'destroy']);

    // Support tickets
    Route::get('/support-tickets', [\App\Http\Controllers\SuperUser\SupportTicketController::class, 'index']);
    Route::post('/support-tickets/{id}/reply', [\App\Http\Controllers\SuperUser\SupportTicketController::class, 'reply']);
    Route::patch('/support-tickets/{id}/resolve', [\App\Http\Controllers\SuperUser\SupportTicketController::class, 'resolve']);

    // Exports (enqueue export job, return job id / URL)
    Route::post('/exports', [\App\Http\Controllers\SuperUser\ExportController::class, 'store']);
    Route::get('/exports', [\App\Http\Controllers\SuperUser\ExportController::class, 'index']);
    Route::get('/exports/{id}', [\App\Http\Controllers\SuperUser\ExportController::class, 'show']);
    Route::get('/exports/{id}/download', [\App\Http\Controllers\SuperUser\ExportController::class, 'download']);

    // Subscriptions & billing management
    Route::get('/subscriptions', [\App\Http\Controllers\SuperUser\SubscriptionController::class, 'index']);
    Route::get('/subscriptions/{id}', [\App\Http\Controllers\SuperUser\SubscriptionController::class, 'show']);
    Route::patch('/subscriptions/{id}/activate', [\App\Http\Controllers\SuperUser\SubscriptionController::class, 'activate']);
    Route::patch('/subscriptions/{id}/deactivate', [\App\Http\Controllers\SuperUser\SubscriptionController::class, 'deactivate']);
    Route::post('/subscriptions/{id}/renew', [\App\Http\Controllers\SuperUser\SubscriptionController::class, 'renew']);
    Route::post('/subscriptions/{id}/trial', [\App\Http\Controllers\SuperUser\SubscriptionController::class, 'assignTrial']);
    Route::get('/subscriptions/{id}/transactions', [\App\Http\Controllers\SuperUser\SubscriptionController::class, 'transactions']);

    // Plan management
    Route::get('/plans', [\App\Http\Controllers\SuperUser\SubscriptionController::class, 'listPlans']);
    Route::post('/plans', [\App\Http\Controllers\SuperUser\SubscriptionController::class, 'storePlan']);
    Route::put('/plans/{id}', [\App\Http\Controllers\SuperUser\SubscriptionController::class, 'updatePlan']);
    Route::delete('/plans/{id}', [\App\Http\Controllers\SuperUser\SubscriptionController::class, 'deletePlan']);

    // Impersonation
    Route::post('/impersonate/{userId}', [\App\Http\Controllers\SuperUser\ImpersonationController::class, 'impersonate']);
    Route::post('/impersonate/revert', [\App\Http\Controllers\SuperUser\ImpersonationController::class, 'revert']);
    Route::get('/impersonate/status', [\App\Http\Controllers\SuperUser\ImpersonationController::class, 'status']);
    Route::get('/impersonate/businesses', [\App\Http\Controllers\SuperUser\ImpersonationController::class, 'listBusinesses']);

    // Chat management - Available to all authenticated users
    Route::get('/chats/available-users', [\App\Http\Controllers\SuperUser\ChatController::class, 'getAvailableUsers']);
    Route::get('/chats/unread-count', [\App\Http\Controllers\SuperUser\ChatController::class, 'unreadCount']);
    Route::get('/chats', [\App\Http\Controllers\SuperUser\ChatController::class, 'index']);
    Route::post('/chats', [\App\Http\Controllers\SuperUser\ChatController::class, 'store']);
    Route::get('/chats/{id}', [\App\Http\Controllers\SuperUser\ChatController::class, 'show']);
    Route::post('/chats/{id}/messages', [\App\Http\Controllers\SuperUser\ChatController::class, 'sendMessage']);
    Route::patch('/chats/{id}/close', [\App\Http\Controllers\SuperUser\ChatController::class, 'closeChat']);
});

// PRODUCTS
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/statistics', [ProductController::class, 'getStatistics']);
Route::get('/products/out-of-stock', [ProductController::class, 'getOutOfStockProducts']);
Route::get('/products/low-stock', [ProductController::class, 'getLowStockProducts']);
Route::get('/products/csv-template', [ProductController::class, 'downloadCSVTemplate']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// DASHBOARD STATS - Add this missing endpoint
Route::get('/dashboard-stats', [DashboardController::class, 'getDashboardStats']);

// PRODUCT CATEGORIES (auth required; admin/superuser for mutations handled in controller)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/product-categories', [\App\Http\Controllers\ProductCategoryController::class, 'index']);
    Route::post('/product-categories', [\App\Http\Controllers\ProductCategoryController::class, 'store']);
    Route::put('/product-categories/{id}', [\App\Http\Controllers\ProductCategoryController::class, 'update']);
    Route::delete('/product-categories/{id}', [\App\Http\Controllers\ProductCategoryController::class, 'destroy']);
    Route::post('/product-categories/bulk-upload', [\App\Http\Controllers\ProductCategoryController::class, 'bulkUpload']);
});

// UOM (Units of Measurement)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/uoms', [\App\Http\Controllers\UOMController::class, 'index']);
    Route::post('/uoms', [\App\Http\Controllers\UOMController::class, 'store']);
    Route::put('/uoms/{id}', [\App\Http\Controllers\UOMController::class, 'update']);
    Route::delete('/uoms/{id}', [\App\Http\Controllers\UOMController::class, 'destroy']);
});

// Promotions & Discounts
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/promotions', [\App\Http\Controllers\PromotionController::class, 'index']);
    Route::post('/promotions', [\App\Http\Controllers\PromotionController::class, 'store']);
    Route::get('/promotions/active', [\App\Http\Controllers\PromotionController::class, 'getActivePromotions']);
    Route::post('/promotions/calculate-discount', [\App\Http\Controllers\PromotionController::class, 'calculateDiscount']);
    Route::get('/promotions/{id}', [\App\Http\Controllers\PromotionController::class, 'show']);
    Route::put('/promotions/{id}', [\App\Http\Controllers\PromotionController::class, 'update']);
    Route::delete('/promotions/{id}', [\App\Http\Controllers\PromotionController::class, 'destroy']);
    Route::post('/promotions/{id}/toggle', [\App\Http\Controllers\PromotionController::class, 'toggleStatus']);
});

// Tax Configurations
Route::middleware('auth:sanctum')->prefix('api/tax-configurations')->group(function () {
    Route::get('/', [TaxConfigurationController::class, 'index']);
    Route::post('/', [TaxConfigurationController::class, 'store']);
    Route::get('/{id}', [TaxConfigurationController::class, 'show']);
    Route::put('/{id}', [TaxConfigurationController::class, 'update']);
    Route::delete('/{id}', [TaxConfigurationController::class, 'destroy']);
    Route::post('/{id}/set-default', [TaxConfigurationController::class, 'setDefault']);
    Route::post('/calculate', [TaxConfigurationController::class, 'calculate']);
});

// Price Groups
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/price-groups', [\App\Http\Controllers\PriceGroupController::class, 'index']);
    Route::post('/price-groups', [\App\Http\Controllers\PriceGroupController::class, 'store']);
    Route::get('/price-groups/{id}', [\App\Http\Controllers\PriceGroupController::class, 'show']);
    Route::put('/price-groups/{id}', [\App\Http\Controllers\PriceGroupController::class, 'update']);
    Route::delete('/price-groups/{id}', [\App\Http\Controllers\PriceGroupController::class, 'destroy']);
});

// Alias /promotions to /api/promotions for authenticated API calls
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/promotions', function (\Illuminate\Http\Request $request) {
        return app()->call('\App\Http\Controllers\PromotionController@index', ['request' => $request]);
    });
    Route::post('/promotions', function (\Illuminate\Http\Request $request) {
        return app()->call('\App\Http\Controllers\PromotionController@store', ['request' => $request]);
    });
    Route::put('/promotions/{id}', function ($id, \Illuminate\Http\Request $request) {
        return app()->call('\App\Http\Controllers\PromotionController@update', ['id' => $id, 'request' => $request]);
    });
    Route::delete('/promotions/{id}', function ($id) {
        return app()->call('\App\Http\Controllers\PromotionController@destroy', ['id' => $id]);
    });
    Route::post('/promotions/{id}/toggle', function ($id) {
        return app()->call('\App\Http\Controllers\PromotionController@toggleStatus', ['id' => $id]);
    });
});

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
    // Get current authenticated user
    Route::get('/user', function () {
        return response()->json(auth()->user()->load('company', 'role'));
    });

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
        
        // Get both system default warehouses (company_id = null) and company-specific warehouses
        $warehouses = \App\Models\Warehouse::where(function($query) use ($user) {
            $query->whereNull('company_id') // System default warehouses
                  ->orWhere('company_id', $user->company_id); // Company-specific warehouses
        })->orderBy('company_id', 'asc')->orderBy('name', 'asc')->get();
        
        return response()->json($warehouses);
    });

    Route::post('/warehouses', function (\Illuminate\Http\Request $request) {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }
        
        if (!in_array(strtolower($user->role->name), ['admin','administrator'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        if (!$user->company_id) {
            return response()->json([
                'error' => 'No company associated with your account',
                'message' => 'Please contact support to link your account to a company',
                'user_id' => $user->id,
                'user_name' => $user->name
            ], 400);
        }
        
        $payload = $request->validate([
            'name'=>'required|string|max:255',
            'type'=>'nullable|string|max:50'
        ]);
        
        $payload['company_id'] = $user->company_id;
        
        \Log::info('Creating warehouse:', [
            'payload' => $payload,
            'user_id' => $user->id,
            'user_company_id' => $user->company_id,
            'user_name' => $user->name
        ]);
        
        $warehouse = \App\Models\Warehouse::create($payload);
        
        \Log::info('Warehouse created:', [
            'warehouse_id' => $warehouse->id,
            'warehouse_company_id' => $warehouse->company_id
        ]);
        
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

    // Payment Methods CRUD for admin
    Route::get('/payment-methods', function () {
        $user = auth()->user();
        if (!$user || !in_array(strtolower($user->role->name), ['admin','administrator'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Get all payment methods with company's enabled status
        $methods = \App\Models\PaymentMethod::where('is_active', true)->get()->map(function($method) use ($user) {
            $pivot = \DB::table('company_payment_methods')
                ->where('company_id', $user->company_id)
                ->where('payment_method_id', $method->id)
                ->first();
            
            // Default enable Cash and M-Pesa for new companies
            $defaultEnabled = in_array($method->name, ['Cash', 'M-Pesa']);
            
            return [
                'id' => $method->id,
                'name' => $method->name,
                'description' => $method->description,
                'is_enabled' => $pivot ? $pivot->is_enabled : $defaultEnabled,
                'created_at' => $method->created_at
            ];
        });
        
        return response()->json($methods);
    });
//customer supplier routes
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::post('/customers', [CustomerController::class, 'store']);    
    Route::get('/suppliers', [SupplierController::class, 'index']); 
    Route::post('/suppliers', [SupplierController::class, 'store']);
    // Get enabled payment methods for current company (for cashiers/POS)
    Route::get('/payment-methods/enabled', [\App\Http\Controllers\PaymentMethodController::class, 'enabled']);

    Route::post('/payment-methods/{id}/toggle', function ($id) {
        $user = auth()->user();
        if (!$user || !in_array(strtolower($user->role->name), ['admin','administrator'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $method = \App\Models\PaymentMethod::findOrFail($id);
        
        $pivot = \DB::table('company_payment_methods')
            ->where('company_id', $user->company_id)
            ->where('payment_method_id', $id)
            ->first();
        
        if ($pivot) {
            // Toggle existing status
            \DB::table('company_payment_methods')
                ->where('company_id', $user->company_id)
                ->where('payment_method_id', $id)
                ->update([
                    'is_enabled' => !$pivot->is_enabled,
                    'updated_at' => now()
                ]);
        } else {
            // Create new entry - determine initial state based on defaults
            $defaultEnabled = in_array($method->name, ['Cash', 'M-Pesa']);
            \DB::table('company_payment_methods')->insert([
                'company_id' => $user->company_id,
                'payment_method_id' => $id,
                'is_enabled' => !$defaultEnabled, // Toggle from default
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        return response()->json(['message' => 'Payment method toggled successfully']);
    });
});

// --- REPORTS API ENDPOINTS (for ReportPage.vue) ---
Route::middleware('auth:sanctum')->group(function () {
    // Sales Report - Call actual ReportController method
    Route::get('/api/reports/sales', [ReportController::class, 'salesReport']);

    // Transfers Report - Call actual ReportController method
    Route::get('/reports/transfers', [ReportController::class, 'transfersReport']);

    // Analytics Report - Call actual ReportController method
    Route::get('/reports/analytics', [ReportController::class, 'analyticsReport']);

    // Promotions Report - Call actual ReportController method
    Route::get('/reports/promotions', [ReportController::class, 'promotionsReport']);

    // Customers Report - Call actual ReportController method
    Route::get('/reports/customers', [ReportController::class, 'customersReport']);

    // Suppliers Report - Call actual ReportController method
    Route::get('/reports/suppliers', [ReportController::class, 'suppliersReport']);

    // DSRS Excel export - Call actual ReportController method
    Route::get('/api/reports/sales/dsrs-export', [ReportController::class, 'exportDSRS']);
    
    // Sales Summary PDF export - Call actual ReportController method
    Route::get('/api/reports/sales/summary-pdf', [ReportController::class, 'exportSalesSummaryPdf']);
    
    // Sales Summary Excel export - Call actual ReportController method
    Route::get('/api/reports/sales/summary-excel', [ReportController::class, 'exportSalesSummaryExcel']);

    // Transfers Excel export - Call actual ReportController method
    Route::get('/api/reports/transfers/export-excel', [ReportController::class, 'exportTransfersExcel']);

    // Promotions CSV export - Call actual ReportController method
    Route::get('/api/reports/promotions/export-csv', [ReportController::class, 'exportPromotionsCsv']);

    // Customers CSV export - Call actual ReportController method
    Route::get('/api/reports/customers/export-csv', [ReportController::class, 'exportCustomersCsv']);

    // Suppliers CSV export - Call actual ReportController method
    Route::get('/api/reports/suppliers/export-csv', [ReportController::class, 'exportSuppliersCsv']);

    // Invoice CRUD
    Route::get('/invoices', [\App\Http\Controllers\InvoiceController::class, 'index']);
    Route::post('/invoices', [\App\Http\Controllers\InvoiceController::class, 'store']);
    Route::get('/invoices/{id}', [\App\Http\Controllers\InvoiceController::class, 'show']);
    Route::put('/invoices/{id}', [\App\Http\Controllers\InvoiceController::class, 'update']);
    Route::delete('/invoices/{id}', [\App\Http\Controllers\InvoiceController::class, 'destroy']);
});
