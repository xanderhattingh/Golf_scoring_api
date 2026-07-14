<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function forgot(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $data['email'])->first();

        // Do not reveal whether the email is on file — always respond the same way
        if ($user) {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $data['email']],
                [
                    'token' => hash('sha256', $token),
                    'created_at' => now(),
                ]
            );

            try {
                Mail::to($data['email'])->send(new ResetPasswordMail($user, $token));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'If an account with that email exists, a reset link has been sent.',
        ]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $data['email'])->first();

        if (! $record) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset link.',
            ], 422);
        }

        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

            return response()->json([
                'success' => false,
                'message' => 'This reset link has expired. Please request a new one.',
            ], 422);
        }

        if (! hash_equals($record->token, hash('sha256', $data['token']))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset link.',
            ], 422);
        }

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset link.',
            ], 422);
        }

        $user->password = $data['password'];
        $user->save();

        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        // Invalidate any existing API tokens so old sessions can't stick around
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully. You can now sign in with your new password.',
        ]);
    }
}
