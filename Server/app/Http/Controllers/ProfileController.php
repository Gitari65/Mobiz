<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\PasswordResetNotification;

class ProfileController extends Controller
{
    /**
     * Get authenticated user's profile
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $user->load('role');
        
        // Provide a public URL for the profile picture if present
        $userArray = $user->toArray();
        $userArray['profile_picture_url'] = $user->profile_picture
            ? \Illuminate\Support\Facades\Storage::url($user->profile_picture)
            : null;

        return response()->json([
            'success' => true,
            'user' => $userArray
        ]);
    }

    /**
     * Update user profile information
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'bio' => $request->bio,
        ]);

        $user->load('role');

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Upload profile picture
     */
    public function uploadPicture(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Delete old profile picture if exists
        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        // Store new profile picture
        $path = $request->file('profile_picture')->store('profile_pictures', 'public');

        $user->update([
            'profile_picture' => $path
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile picture uploaded successfully',
            'profile_picture' => $path,
            'profile_picture_url' => \Illuminate\Support\Facades\Storage::url($path)
        ]);
    }

    /**
     * Remove profile picture
     */
    public function removePicture(Request $request)
    {
        $user = $request->user();

        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        $user->update([
            'profile_picture' => null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile picture removed successfully',
            'profile_picture' => null,
            'profile_picture_url' => null
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 422);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->new_password),
            'must_change_password' => false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Request password reset via email
     */
    public function requestPasswordReset(Request $request)
    {
        $user = $request->user();

        // Generate 8-character OTP (alphanumeric)
        $otp = strtoupper(Str::random(8));

        // Set OTP as the new temporary password
        $user->update([
            'password' => Hash::make($otp),
            'must_change_password' => true,
            'password_reset_token' => null,
            'password_reset_token_expires_at' => null
        ]);

        // Send OTP via email
        try {
            Mail::to($user->email)->send(new PasswordResetNotification($user, $otp));
            
            return response()->json([
                'success' => true,
                'message' => 'New temporary password sent to your email'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset email. Please try again later.'
            ], 500);
        }
    }

    /**
     * Reset password using token from email
     */
    public function resetPasswordWithToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Check if token exists and is not expired
        if (!$user->password_reset_token || !$user->password_reset_token_expires_at) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset token'
            ], 422);
        }

        if (now()->gt($user->password_reset_token_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Reset token has expired'
            ], 422);
        }

        // Verify token
        if (!Hash::check($request->token, $user->password_reset_token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid reset token'
            ], 422);
        }

        // Update password and clear reset token
        $user->update([
            'password' => Hash::make($request->password),
            'password_reset_token' => null,
            'password_reset_token_expires_at' => null,
            'must_change_password' => false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully'
        ]);
    }
}
