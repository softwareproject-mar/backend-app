<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\RegisterWithOtpRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\RequestOtpRequest;
use App\Http\Requests\VerifyResetOtpRequest;
use App\Http\Resources\UserResource;
use App\Jobs\SendOtpEmailJob;
use App\Models\EmailVerification;
use App\Models\User;
use App\Services\AuthService;
use App\Services\OtpService;
use App\Services\PasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $service,
        private OtpService $otpService,
        private PasswordResetService $passwordResetService
    ) {
    }

    /**
     * Request OTP for email verification.
     */
    public function requestOtp(RequestOtpRequest $request)
    {
        $email = $request->validated()['email'];

        // Check rate limit
        if (!$this->otpService->checkRateLimit($email)) {
            return response()->json([
                'message' => 'Too many requests. Please try again later.',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Generate and store OTP
        $otp = $this->otpService->generateOtp($email);
        $this->otpService->storeOtp($email, $otp, 'register');

        // Dispatch job to send email
        SendOtpEmailJob::dispatch($email, $otp, 'register');

        $expiryMinutes = config('otp.expiry_minutes', 5);

        return response()->json([
            'message' => 'OTP has been sent to your email',
            'email' => $email,
            'expires_in' => $expiryMinutes * 60, // seconds
        ], Response::HTTP_OK);
    }

    /**
     * Register a new user with OTP verification.
     */
    public function register(RegisterWithOtpRequest $request)
    {
        $data = $request->validated();

        // Verify OTP
        if (!$this->otpService->verifyOtp($data['email'], $data['otp'], 'register')) {
            return response()->json([
                'message' => 'Invalid or expired OTP code',
                'errors' => [
                    'otp' => ['The OTP code is invalid or has expired.'],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Create user with email verified
        $user = $this->service->register($data, true);

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Delete used OTP
        EmailVerification::where('email', $data['email'])
            ->where('purpose', 'register')
            ->delete();

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], Response::HTTP_CREATED);
    }

    /**
     * Request OTP for password reset.
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $email = $request->validated()['email'];

        if (! $this->otpService->checkRateLimit($email)) {
            return response()->json([
                'message' => 'Too many requests. Please try again later.',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $otp = $this->otpService->generateOtp($email);
        $this->otpService->storeOtp($email, $otp, 'password_reset');
        SendOtpEmailJob::dispatch($email, $otp, 'password_reset');

        $expiryMinutes = config('otp.expiry_minutes', 5);

        return response()->json([
            'message' => 'OTP has been sent to your email',
            'email' => $email,
            'expires_in' => $expiryMinutes * 60,
        ], Response::HTTP_OK);
    }

    /**
     * Verify password reset OTP and issue reset token.
     */
    public function verifyResetOtp(VerifyResetOtpRequest $request)
    {
        $data = $request->validated();
        $email = $data['email'];

        if (! $this->otpService->verifyOtp($email, $data['otp'], 'password_reset')) {
            return response()->json([
                'message' => 'Invalid or expired OTP code',
                'errors' => [
                    'otp' => ['The OTP code is invalid or has expired.'],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $resetToken = $this->passwordResetService->generateToken($email);

        EmailVerification::where('email', $email)
            ->where('purpose', 'password_reset')
            ->delete();

        return response()->json([
            'message' => 'OTP verified. Use the reset token to update your password.',
            'reset_token' => $resetToken,
        ], Response::HTTP_OK);
    }

    /**
     * Reset password using previously issued reset token.
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $data = $request->validated();
        $email = $data['email'];

        if (! $this->passwordResetService->verifyToken($email, $data['reset_token'])) {
            return response()->json([
                'message' => 'Invalid or expired reset token',
                'errors' => [
                    'reset_token' => ['The reset token is invalid or has expired.'],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::where('email', $email)->firstOrFail();
        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        $this->passwordResetService->revokeToken($email);

        return response()->json([
            'message' => 'Password has been reset successfully.',
        ], Response::HTTP_OK);
    }

    /**
     * Login user and return token.
     */
    public function login(LoginRequest $request)
    {
        $result = $this->service->login($request->validated());

        return response()->json([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    /**
     * Logout user (revoke current token).
     */
    public function logout(Request $request)
    {
        $this->service->logout($request->user());

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Get current authenticated user.
     */
    public function me(Request $request)
    {
        return new UserResource($request->user());
    }
}
