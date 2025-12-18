<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\RegisterWithOtpRequest;
use App\Http\Requests\RequestOtpRequest;
use App\Http\Resources\UserResource;
use App\Jobs\SendOtpEmailJob;
use App\Services\AuthService;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $service,
        private OtpService $otpService
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
        $this->otpService->storeOtp($email, $otp);

        // Dispatch job to send email
        SendOtpEmailJob::dispatch($email, $otp);

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
        if (!$this->otpService->verifyOtp($data['email'], $data['otp'])) {
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
        \App\Models\EmailVerification::where('email', $data['email'])->delete();

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], Response::HTTP_CREATED);
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
