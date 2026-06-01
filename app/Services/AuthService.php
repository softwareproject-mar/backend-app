<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register a new user.
     *
     * @param  array<string, mixed>  $data
     */
    public function register(array $data, bool $otpVerified = false): User
    {
        $role = $data['role'] ?? 'user';
        $isActive = filter_var($data['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // Role user tidak boleh mengaktifkan diri sendiri lewat endpoint register.
        if ($role === 'user') {
            $isActive = false;
        }

        $registrationStatus = $data['registration_status'] ?? null;
        if ($registrationStatus === null) {
            $registrationStatus = ($role === 'user' && ! $isActive)
                ? User::REGISTRATION_PENDING
                : User::REGISTRATION_APPROVED;
        }

        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $role,
            'is_active' => $isActive,
            'registration_status' => $registrationStatus,
            'device_id' => $role === 'user' ? (string) ($data['device_id'] ?? '') : null,
        ];

        // If OTP verified, mark email as verified
        if ($otpVerified) {
            $userData['email_verified_at'] = now();
        }

        return User::create($userData);
    }

    /**
     * Pendaftar yang pernah ditolak: daftar ulang dengan email sama (setelah OTP).
     * Token lama dicabut; status kembali menunggu persetujuan admin.
     *
     * @param  array<string, mixed>  $data  name, password (plain), email
     */
    public function reapplyRejectedUser(User $user, array $data, bool $otpVerified = true): User
    {
        if ($user->role !== 'user' || $user->registration_status !== User::REGISTRATION_REJECTED) {
            throw new \InvalidArgumentException('Hanya akun anggota berstatus ditolak yang dapat mendaftar ulang lewat jalur ini.');
        }

        $user->tokens()->delete();

        $updates = [
            'name' => $data['name'],
            'password' => $data['password'],
            'role' => 'user',
            'is_active' => false,
            'registration_status' => User::REGISTRATION_PENDING,
            'registration_reviewed_at' => null,
            'registration_reviewed_by' => null,
            'no_agt' => null,
            'id_kel' => null,
            'device_id' => (string) ($data['device_id'] ?? ''),
        ];

        if ($otpVerified) {
            $updates['email_verified_at'] = now();
        }

        $user->update($updates);

        return $user->fresh() ?? $user;
    }

    /**
     * Attempt to log in a user and return token + user.
     *
     * @param  array<string, mixed>  $credentials
     * @return array<string, mixed>
     */
    public function login(array $credentials): array
    {
        $emailNorm = mb_strtolower(trim((string) ($credentials['email'] ?? '')));
        $grammar = User::query()->getQuery()->getGrammar();
        $emailCol = $grammar->wrap('email');
        $user = User::query()
            ->whereRaw('LOWER(TRIM('.$emailCol.')) = ?', [$emailNorm])
            ->first();

        $hash = $user ? rtrim((string) ($user->getRawOriginal('password') ?? '')) : '';

        if (! $user || $hash === '' || ! Hash::check((string) $credentials['password'], $hash)) {
            throw new AuthenticationException('Email atau password anda salah.');
        }

        if (! $user->is_active) {
            throw new AuthenticationException('Akun Anda belum terdaftar.');
        }

        $incomingDeviceId = trim((string) ($credentials['device_id'] ?? ''));

        if ($user->role === 'user') {
            $storedDeviceId = trim((string) ($user->device_id ?? ''));

            if ($incomingDeviceId === '') {
                throw ValidationException::withMessages([
                    'device_id' => ['ID perangkat diperlukan untuk login dari aplikasi.'],
                ]);
            }

            if ($storedDeviceId === '') {
                $user->device_id = $incomingDeviceId;
                $user->save();
            } elseif ($storedDeviceId !== $incomingDeviceId) {
                throw ValidationException::withMessages([
                    'device_id' => ['Akun ini terdaftar di perangkat lain. Hubungi admin untuk reset perangkat.'],
                ]);
            }
        } elseif (in_array($user->role, ['admin', 'super_admin'], true)) {
            // Web admin: tanpa device_id — izinkan, jangan ubah device_id yang sudah terikat aplikasi.
            if ($incomingDeviceId !== '') {
                $storedDeviceId = trim((string) ($user->device_id ?? ''));

                if ($storedDeviceId === '') {
                    $user->device_id = $incomingDeviceId;
                    $user->save();
                } elseif ($storedDeviceId !== $incomingDeviceId) {
                    throw ValidationException::withMessages([
                        'device_id' => ['Akun ini terdaftar di perangkat lain. Hubungi admin untuk reset perangkat.'],
                    ]);
                }
            }
        }

        // Update last login (Firebird bisa melempar -913 saat ada update bersamaan).
        $this->updateLastLoginWithRetry($user);

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Logout user by revoking current token.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    private function updateLastLoginWithRetry(User $user): void
    {
        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                // Hindari update UPDATED_AT agar konflik write lebih kecil.
                $user->timestamps = false;
                $user->forceFill(['last_login_at' => now()])->saveQuietly();
                $user->timestamps = true;

                return;
            } catch (QueryException $e) {
                $user->timestamps = true;

                $isDeadlock = str_contains($e->getMessage(), '-913');
                if (! $isDeadlock || $attempt === $maxAttempts) {
                    throw $e;
                }

                usleep(100000 * $attempt); // backoff 100ms, 200ms
                $user->refresh();
            }
        }
    }
}
