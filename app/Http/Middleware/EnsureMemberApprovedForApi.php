<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\MemberScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMemberApprovedForApi
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (MemberScope::normalizeMemberRole($user) !== 'user') {
            return $next($request);
        }

        if ($user->is_active && $user->registration_status === User::REGISTRATION_APPROVED) {
            return $next($request);
        }

        $message = match ($user->registration_status) {
            User::REGISTRATION_REJECTED => 'Pendaftaran Anda ditolak. Hubungi administrator jika perlu bantuan.',
            User::REGISTRATION_PENDING => 'Akun menunggu persetujuan administrator. Anda belum dapat mengakses fitur ini.',
            default => 'Akun Anda tidak dapat mengakses fitur ini.',
        };

        return response()->json([
            'message' => $message,
        ], Response::HTTP_FORBIDDEN);
    }
}
