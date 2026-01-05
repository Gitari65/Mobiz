<?php
namespace App\Http\Controllers\SuperUser;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Company; // <-- added
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail; // <-- ADDED
use App\Mail\PasswordResetNotification; // <-- ADDED
use App\Mail\WelcomeOneTimePassword; // reuse existing mailable
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
class UserController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        // Authorization: only superusers
        $this->authorize('viewAny', User::class);

        $query = User::with('role','company');

        // Search by q
        if ($request->filled('q')) {
            $query->where(function($q) use ($request) {
                $q->where('name','like','%'.$request->q.'%')
                  ->orWhere('email','like','%'.$request->q.'%');
            });
        }

        // Filter by role name
        if ($request->filled('role')) {
            $role = $request->role;
            $query->whereHas('role', function($qr) use ($role) {
                $qr->where('name', $role);
            });
        }

        // Filter by company
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Filter by status (verified/unverified)
        if ($request->filled('status')) {
            $status = strtolower($request->status);
            if ($status === 'verified') $query->where('verified', true);
            if ($status === 'unverified') $query->where('verified', false);
        }

        $perPage = (int) $request->get('per_page', 20);
        $users = $query->orderBy('created_at','desc')->paginate($perPage);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $payload = $request->validate([
            'name'=>'required|string|max:255',
            'email'=>'required|email|unique:users,email',
            'password'=>'required|string|min:6',
            'role_id'=>'required|exists:roles,id',
            'company_id'=>'nullable|exists:companies,id',
            'company' => 'nullable|array',
            'company.name' => 'required_with:company|string|max:255',
            'company.email' => 'nullable|email',
            'company.phone' => 'nullable|string|max:50',
            'company.category' => 'nullable|string|max:100',
            'company.address' => 'nullable|string|max:500',
        ]);

        // Keep plain password for email before hashing
        $plainPassword = $payload['password'];

        // If nested company provided, create it and use its id
        if (!empty($payload['company']) && empty($payload['company_id'])) {
            $c = $payload['company'];
            $company = Company::create([
                'name' => $c['name'],
                'email' => $c['email'] ?? null,
                'phone' => $c['phone'] ?? null,
                'category' => $c['category'] ?? null,
                'address' => $c['address'] ?? null,
                'active' => true, // created by superuser -> active
            ]);
            $payload['company_id'] = $company->id;
        }

        // Ensure password hashed and set default flags
        $payload['password'] = Hash::make($payload['password']);
        $payload['verified'] = true; // created by superuser -> verified
        $payload['must_change_password'] = true;

        // Only keep allowed user fields
        $userData = array_intersect_key($payload, array_flip(['name','email','password','role_id','company_id','verified','must_change_password']));

        $user = User::create($userData);

        Log::info('SuperUser created user', [
            'by' => auth()->user()->id,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_name' => $user->name
        ]);

        // Send welcome OTP/password email (best-effort, don't break creation on mail failures)
        try {
            Mail::to($user->email)->send(new WelcomeOneTimePassword($user, $plainPassword));
            Log::info('Welcome OTP email sent', ['user_id' => $user->id]);
        } catch (\Throwable $e) {
            Log::error('Failed to send welcome email', ['error'=>$e->getMessage(),'user_id'=>$user->id]);
        }

        return response()->json($user->load('role','company'), 201);
    }

    public function show($id)
    {
        $userModel = User::with('role','company')->findOrFail($id);
        $this->authorize('view', $userModel);
        return response()->json($userModel);
    }

    public function update(Request $request,$id)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        $data = $request->validate([
            'name'=>'nullable|string|max:255',
            'email'=>['nullable','email', Rule::unique('users','email')->ignore($id)],
            'role_id'=>'nullable|exists:roles,id',
            'company_id'=>'nullable|exists:companies,id'
        ]);

        $user->update($data);
        Log::info('SuperUser updated user', ['by'=>auth()->user()->id, 'user_id'=>$user->id]);

        return response()->json($user->load('role','company'));
    }

    public function activate($id)
    {
        $this->authorize('activate', User::class);
        $user = User::findOrFail($id);
        $user->verified = true;
        $user->save();
        Log::info('SuperUser activated user', ['by'=>auth()->user()->id, 'user_id'=>$user->id]);
        return response()->json(['message'=>'Activated']);
    }

    public function deactivate($id)
    {
        $this->authorize('deactivate', User::class);
        $user = User::findOrFail($id);
        $user->verified = false;
        $user->save();
        Log::info('SuperUser deactivated user', ['by'=>auth()->user()->id, 'user_id'=>$user->id]);
        return response()->json(['message'=>'Deactivated']);
    }

    public function resetPassword($id)
    {
        $this->authorize('resetPassword', User::class);
        $user = User::findOrFail($id);
        $new = Str::random(10);
        $user->password = Hash::make($new);
        $user->must_change_password = true; // force password change on next login
        $user->save();
        Log::info('SuperUser reset password', ['by'=>auth()->user()->id, 'user_id'=>$user->id]);

        // Send password reset email (best-effort)
        try {
            Mail::to($user->email)->send(new PasswordResetNotification($user, $new));
            Log::info('Password reset email sent', ['user_id' => $user->id]);
        } catch (\Throwable $e) {
            Log::error('Failed to send password reset email', ['error'=>$e->getMessage(),'user_id'=>$user->id]);
        }

        // Return new temp password only for admin/dev debugging; remove in production.
        return response()->json(['temp_password' => $new]);
    }
}
