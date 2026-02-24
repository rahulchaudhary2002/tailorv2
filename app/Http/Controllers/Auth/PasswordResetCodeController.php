<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PasswordResetCodeController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $user = User::where('email', $request->email)->first();

        $code = random_int(100000, 999999);

        cache()->put('password_reset_code_' . $request->email, $code, now()->addMinutes(15));

        Mail::to($request->email)->send(new PasswordResetCodeMail($code, $user));

        return response()->json([
            'status' => 'success',
            'message' => 'A 6-digit reset code has been sent to your email address.'
        ], 200);
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'code' => ['required', 'digits:6'],
        ]);

        $cachedCode = cache()->get('password_reset_code_' . $request->email);

        if ($cachedCode && $cachedCode == $request->code) {
            cache()->forget('password_reset_code_' . $request->email);
            return response()->json(['status' => 'success', 'message' => 'Code verified. You can now reset your password.']);
        }

        return response()->json(['status' => 'error', 'message' => 'Invalid or expired code.'], 400);
    }
}
