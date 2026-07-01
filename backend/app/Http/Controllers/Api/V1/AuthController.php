<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/v1/auth/register
     * Creates a `user` role account and returns a personal access token.
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name'     => $request->string('name'),
            'email'    => $request->string('email'),
            'phone'    => $request->input('phone'),
            'password' => $request->string('password'),
            'role'     => 'user',
            'is_active'=> true, // explicit so the returned model reflects it
        ]);

        event(new Registered($user));

        return ApiResponse::success(
            $this->withToken($user),
            'Registration successful.',
            201
        );
    }

    /**
     * POST /api/v1/auth/login
     * Accepts email OR phone in the `login` field, plus password.
     */
    public function login(LoginRequest $request)
    {
        $login = $request->string('login');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::where($field, $login)->first();

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['These credentials do not match our records.'],
            ]);
        }

        if (! $user->is_active) {
            return ApiResponse::error('Your account has been deactivated.', 403);
        }

        return ApiResponse::success(
            $this->withToken($user),
            'Login successful.'
        );
    }

    /**
     * POST /api/v1/auth/logout  (auth)
     * Revokes the token used for the current request.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return ApiResponse::success(null, 'Logged out successfully.');
    }

    /** GET /api/v1/auth/me  (auth) */
    public function me(Request $request)
    {
        return ApiResponse::success(
            new UserResource($request->user()),
            'Authenticated user.'
        );
    }

    /**
     * POST /api/v1/auth/forgot-password
     * Sends a reset link to the registered email (always 200 to avoid leaking
     * which emails exist).
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        Password::sendResetLink($request->only('email'));

        return ApiResponse::success(
            null,
            'If that email is registered, a password reset link has been sent.'
        );
    }

    /**
     * POST /api/v1/auth/reset-password
     * Completes the reset using the emailed token.
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Invalidate every existing session/token after a reset.
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return ApiResponse::success(null, 'Password has been reset. Please log in.');
    }

    /** Build the standard auth payload: user + bearer token. */
    protected function withToken(User $user): array
    {
        $token = $user->createToken('api')->plainTextToken;

        return [
            'user'       => new UserResource($user),
            'token'      => $token,
            'token_type' => 'Bearer',
        ];
    }
}
