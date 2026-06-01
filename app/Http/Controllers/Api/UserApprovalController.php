<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveMemberRegistrationRequest;
use App\Http\Resources\UserResource;
use App\Jobs\SendAccountActivationJob;
use App\Models\User;
use App\Support\CaseInsensitiveSearch;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class UserApprovalController extends Controller
{
    private function memberRegistrationBase(): \Illuminate\Database\Eloquent\Builder
    {
        return User::query()->where('role', 'user')->with('kelSah');
    }

    /**
     * Isi relasi registrationReviewer lewat satu query whereIn (nama/email untuk UserResource).
     * Lebih andal daripada with() self-BelongsTo terbatas kolom di beberapa driver.
     */
    private function hydrateRegistrationReviewersForCollection(EloquentCollection $members): void
    {
        if ($members->isEmpty()) {
            return;
        }

        $ids = $members
            ->pluck('registration_reviewed_by')
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->unique()
            ->values();
        if ($ids->isEmpty()) {
            return;
        }

        $reviewers = User::query()
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'email']);

        // Map by int id: keyBy()->get($rid) can miss when DB returns string id vs int keys.
        $byId = [];
        foreach ($reviewers as $r) {
            $byId[(int) $r->id] = $r;
        }

        foreach ($members as $member) {
            $rid = $member->registration_reviewed_by;
            if ($rid === null || $rid === '') {
                continue;
            }
            $member->setRelation('registrationReviewer', $byId[(int) $rid] ?? null);
        }
    }

    private function hydrateRegistrationReviewerForUser(User $user): void
    {
        $rid = $user->registration_reviewed_by;
        if ($rid === null) {
            return;
        }

        $rev = User::query()->whereKey($rid)->first(['id', 'name', 'email']);
        $user->setRelation('registrationReviewer', $rev);
    }

    private function freshUserForResource(User $user): User
    {
        $fresh = $user->fresh();
        if (! $fresh instanceof User) {
            return $user;
        }
        $this->hydrateRegistrationReviewerForUser($fresh);

        return $fresh;
    }

    /**
     * Ringkasan jumlah pendaftar anggota: menunggu, ditolak, disetujui.
     */
    public function stats(): JsonResponse
    {
        $base = $this->memberRegistrationBase();

        return response()->json([
            'pending' => (clone $base)->where('registration_status', User::REGISTRATION_PENDING)->count(),
            'rejected' => (clone $base)->where('registration_status', User::REGISTRATION_REJECTED)->count(),
            'approved' => (clone $base)->where('registration_status', User::REGISTRATION_APPROVED)->count(),
        ]);
    }

    /**
     * Daftar gabungan untuk halaman persetujuan (satu tabel + filter).
     *
     * Query: status = all|pending|approved|rejected (default pending), search, page, per_page (max 100).
     */
    public function queue(Request $request): JsonResponse
    {
        $rawStatus = $request->query('status', User::REGISTRATION_PENDING);
        if (is_array($rawStatus)) {
            $rawStatus = $rawStatus === [] ? User::REGISTRATION_PENDING : end($rawStatus);
        }
        $status = strtolower(trim((string) $rawStatus));
        $allowed = ['all', User::REGISTRATION_PENDING, User::REGISTRATION_APPROVED, User::REGISTRATION_REJECTED];
        if (! in_array($status, $allowed, true)) {
            return response()->json([
                'message' => 'Parameter status tidak valid. Gunakan: all, pending, approved, atau rejected.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $query = $this->memberRegistrationBase()->orderByDesc('updated_at');

        if ($status !== 'all') {
            $query->where('registration_status', $status);
        }

        if ($request->filled('search')) {
            CaseInsensitiveSearch::applyOrLikeContainsGroup($query, [
                'name',
                'email',
                'role',
                'registration_status',
                'no_agt',
                'id_kel',
            ], (string) $request->search);
        }

        $perPage = min(max($request->integer('per_page', 20), 1), 100);
        $users = $query->paginate($perPage);

        // Jangan pass LengthAwarePaginator ke collection di dalam key "data":
        // Laravel akan menge-nest jadi { data: { data: [...], links, meta } } sehingga client yang
        // mengharapkan data[] langsung mendapat array kosong.
        $collection = $users->getCollection();
        $this->hydrateRegistrationReviewersForCollection($collection);
        $rows = UserResource::collection($collection)->resolve($request);

        return response()->json([
            'data' => $rows,
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * List pending users (registration_status = pending).
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->memberRegistrationBase()
            ->where('registration_status', User::REGISTRATION_PENDING)
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            CaseInsensitiveSearch::applyOrLikeContainsGroup($query, [
                'name',
                'email',
                'role',
                'registration_status',
                'no_agt',
                'id_kel',
            ], (string) $request->search);
        }

        $perPage = min($request->integer('per_page', 10000), 50000);
        $users = $query->paginate($perPage);

        $collection = $users->getCollection();
        $this->hydrateRegistrationReviewersForCollection($collection);
        $rows = UserResource::collection($collection)->resolve($request);

        return response()->json([
            'data' => $rows,
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * List rejected registrations (registration_status = rejected).
     */
    public function rejected(Request $request): JsonResponse
    {
        $query = $this->memberRegistrationBase()
            ->where('registration_status', User::REGISTRATION_REJECTED)
            ->orderBy('updated_at', 'desc');

        if ($request->filled('search')) {
            CaseInsensitiveSearch::applyOrLikeContainsGroup($query, [
                'name',
                'email',
                'role',
                'registration_status',
                'no_agt',
                'id_kel',
            ], (string) $request->search);
        }

        $perPage = min($request->integer('per_page', 10000), 50000);
        $users = $query->paginate($perPage);

        $collection = $users->getCollection();
        $this->hydrateRegistrationReviewersForCollection($collection);
        $rows = UserResource::collection($collection)->resolve($request);

        return response()->json([
            'data' => $rows,
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * List approved registrations (registration_status = approved).
     */
    public function approved(Request $request): JsonResponse
    {
        $query = $this->memberRegistrationBase()
            ->where('registration_status', User::REGISTRATION_APPROVED)
            ->orderByDesc('updated_at');

        if ($request->filled('search')) {
            CaseInsensitiveSearch::applyOrLikeContainsGroup($query, [
                'name',
                'email',
                'role',
                'registration_status',
                'no_agt',
                'id_kel',
            ], (string) $request->search);
        }

        $perPage = min($request->integer('per_page', 10000), 50000);
        $users = $query->paginate($perPage);

        $collection = $users->getCollection();
        $this->hydrateRegistrationReviewersForCollection($collection);
        $rows = UserResource::collection($collection)->resolve($request);

        return response()->json([
            'data' => $rows,
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Approve user (activate + send email).
     */
    public function approve(ApproveMemberRegistrationRequest $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->registration_status !== User::REGISTRATION_PENDING) {
            return response()->json([
                'message' => 'Pendaftaran ini sudah diproses sebelumnya.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $request->validated();

        // Pemroses selalu user pada token (Sanctum); body registration_reviewed_by tidak dipakai.
        $reviewerId = (int) $request->user()->id;

        if ($reviewerId < 1 || ! User::query()->whereKey($reviewerId)->exists()) {
            return response()->json([
                'message' => 'ID pemroses tidak valid.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $payload = [
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'registration_reviewed_at' => now(),
            'registration_reviewed_by' => $reviewerId,
            'updated_at' => now(),
        ];
        $payload['id_kel'] = $validated['id_kel'] !== null && $validated['id_kel'] !== ''
            ? $validated['id_kel']
            : null;

        // Jangan pakai DB::transaction() di sini: Firebird/PDO sering melempar
        // "There is already an active transaction" (tanpa savepoint / transaksi implisit).
        // Satu UPDATE bersyarat sudah atomik di server.
        $affected = User::query()
            ->whereKey($user->id)
            ->where('registration_status', User::REGISTRATION_PENDING)
            ->update($payload);

        if ($affected === 0) {
            return response()->json([
                'message' => 'Pendaftaran ini sudah diproses sebelumnya.',
                'errors' => [
                    'registration_status' => ['Pendaftaran ini sudah diproses sebelumnya.'],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->refresh();
        $after = $user;
        Log::info('UserApproval.approve persisted', [
            'member_user_id' => $after?->id,
            'registration_reviewed_by' => $after?->registration_reviewed_by,
            'registration_reviewed_at' => $this->datetimeForLog($after?->registration_reviewed_at),
            'db_connection' => config('database.default'),
        ]);

        SendAccountActivationJob::dispatch($after instanceof User ? $after : $user->fresh())->afterResponse();

        return response()->json([
            'message' => 'Akun berhasil diaktifkan. Email aktivasi telah dikirim.',
            'data' => new UserResource($this->freshUserForResource($user)),
        ]);
    }

    /**
     * Tolak pendaftaran (tetap nonaktif, tidak kirim email aktivasi).
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->registration_status !== User::REGISTRATION_PENDING) {
            return response()->json([
                'message' => 'Pendaftaran ini sudah diproses sebelumnya.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Pemroses selalu user pada token (Sanctum); body registration_reviewed_by tidak dipakai.
        $reviewerId = (int) $request->user()->id;

        if ($reviewerId < 1 || ! User::query()->whereKey($reviewerId)->exists()) {
            return response()->json([
                'message' => 'ID pemroses tidak valid.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $affected = User::query()
            ->whereKey($user->id)
            ->where('registration_status', User::REGISTRATION_PENDING)
            ->update([
                'registration_status' => User::REGISTRATION_REJECTED,
                'registration_reviewed_at' => now(),
                'registration_reviewed_by' => $reviewerId,
                'updated_at' => now(),
            ]);

        if ($affected === 0) {
            return response()->json([
                'message' => 'Pendaftaran ini sudah diproses sebelumnya.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->refresh();

        return response()->json([
            'message' => 'Pendaftaran ditolak. Akun tidak dapat login.',
            'data' => new UserResource($this->freshUserForResource($user)),
        ]);
    }

    /**
     * Reset binding perangkat untuk akun anggota yang sudah disetujui.
     */
    public function resetDevice(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->role !== 'user') {
            return response()->json([
                'message' => 'Reset perangkat hanya berlaku untuk akun anggota.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($user->registration_status !== User::REGISTRATION_APPROVED) {
            return response()->json([
                'message' => 'Reset perangkat hanya untuk akun anggota yang sudah disetujui.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->update([
            'device_id' => null,
        ]);

        Log::info('UserApproval.resetDevice executed', [
            'target_user_id' => $user->id,
            'target_user_email' => $user->email,
            'actor_user_id' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'Perangkat akun berhasil direset. Pengguna dapat login ulang dari perangkat baru.',
            'data' => new UserResource($this->freshUserForResource($user)),
        ], Response::HTTP_OK);
    }

    /**
     * Beberapa driver DB mengembalikan kolom datetime sebagai string setelah refresh;
     * jangan panggil toIso8601String() langsung pada atribut model.
     */
    private function datetimeForLog(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->toIso8601String();
        }
        if (is_string($value)) {
            return $value;
        }

        return null;
    }
}
