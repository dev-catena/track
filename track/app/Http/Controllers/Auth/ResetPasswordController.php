<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after set/reset password.
     *
     * @var string
     */
    protected function redirectTo()
    {
        $role = Auth::user()->role;

        return match ($role) {
            'superadmin' => '/SuperAdmin/dashboard',
            'admin' => '/Admin/dashboard',
            'manager' => '/Manager/dashboard',
            default => '/dashboard',
        };
    }


    /**
     * Override the default password reset logic.
     */
    public function reset(Request $request)
    {
        // Custom validation
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        // Password reset logic
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'plain_password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                Auth::login($user);
            }
        );

        return $status === Password::PASSWORD_RESET
        ? redirect($this->redirectPath())
        : back()->withErrors(['email' => [__($status)]]);
    }
}
