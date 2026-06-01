<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Anggota;
use App\Models\DataAo;
use App\Models\DataLo;
use App\Models\DataPengelola;
use App\Models\KetuaKs;
use App\Models\SekretarisKs;
use App\Models\User;
use App\Support\CaseInsensitiveSearch;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    /**
     * Ukuran chunk untuk whereIn(NO_AGT, …). Tanpa ini, per_page besar (mis. 50k) menghasilkan SQL
     * sangat panjang → MySQL bisa gagal / timeout → HTTP 500 pada GET super-admin/users.
     */
    private const JABATAN_WHERE_IN_CHUNK = 500;

    /**
     * Isi atribut virtual "jabatan" berdasarkan no_agt user:
     * ketua, sekretaris, ao, lo, pengelola; jika tidak ada -> null.
     */
    private function hydrateJabatanForCollection(EloquentCollection $users): void
    {
        if ($users->isEmpty()) {
            return;
        }

        $noAgtList = $users
            ->pluck('no_agt')
            ->filter(fn ($v) => is_string($v) && trim($v) !== '')
            ->map(fn ($v) => trim((string) $v))
            ->unique()
            ->values();

        if ($noAgtList->isEmpty()) {
            foreach ($users as $user) {
                $user->setAttribute('jabatan', null);
            }

            return;
        }

        $jabatanByNoAgt = [];

        $register = static function (array $noAgts, string $jabatan) use (&$jabatanByNoAgt): void {
            foreach ($noAgts as $noAgt) {
                $key = trim((string) $noAgt);
                if ($key === '' || array_key_exists($key, $jabatanByNoAgt)) {
                    continue;
                }
                $jabatanByNoAgt[$key] = $jabatan;
            }
        };

        $noAgtArray = $noAgtList->all();

        // Gunakan get(['NO_AGT']) lalu ->NO_AGT melalui model (FirebirdLegacyModel sudah
        // menormalisasi atribut ke UPPERCASE), bukan pluck() langsung dari Query Builder
        // yang akan memanggil $stdClass->NO_AGT dan gagal karena PDO CASE_LOWER.
        $getNoAgts = static function (string $modelClass, array $chunk): array {
            return $modelClass::query()
                ->whereIn('NO_AGT', $chunk)
                ->get(['NO_AGT'])
                ->map(static fn ($m) => trim((string) ($m->NO_AGT ?? '')))
                ->filter(static fn (string $s) => $s !== '')
                ->values()
                ->all();
        };

        try {
            foreach (array_chunk($noAgtArray, self::JABATAN_WHERE_IN_CHUNK) as $chunk) {
                if ($chunk === []) {
                    continue;
                }
                $register($getNoAgts(KetuaKs::class, $chunk), 'ketua');
                $register($getNoAgts(SekretarisKs::class, $chunk), 'sekretaris');
                $register($getNoAgts(DataAo::class, $chunk), 'ao');
                $register($getNoAgts(DataLo::class, $chunk), 'lo');
                $register($getNoAgts(DataPengelola::class, $chunk), 'pengelola');
            }
        } catch (\Throwable $e) {
            Log::warning('hydrateJabatanForCollection gagal; kolom jabatan dikosongkan.', [
                'message' => $e->getMessage(),
                'users_count' => $users->count(),
                'no_agt_distinct' => count($noAgtArray),
            ]);
            $jabatanByNoAgt = [];
        }

        foreach ($users as $user) {
            $key = trim((string) ($user->no_agt ?? ''));
            $user->setAttribute('jabatan', $key !== '' ? ($jabatanByNoAgt[$key] ?? null) : null);
        }
    }

    /**
     * List all users (semua user termasuk super_admin).
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->with([
                'kelSah' => static function ($q): void {
                    $q->select('ID_KEL', 'NAMA_KEL');
                },
            ])
            ->orderBy('created_at', 'desc');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
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

        $perPage = min($request->integer('per_page', 10000), 50000);
        $users = $query->paginate($perPage);
        $this->hydrateJabatanForCollection($users->getCollection());

        return response()->json([
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * List all users (sama seperti index - semua user).
     */
    public function admins(Request $request): JsonResponse
    {
        return $this->index($request);
    }

    /**
     * Create user (admin).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => [
                'required',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
            'role' => ['required', 'string', Rule::in(['admin', 'user'])],
            'no_agt' => [
                'sometimes',
                'nullable',
                'string',
                'max:15',
                'exists:anggota,NO_AGT',
                'unique:users,no_agt',
            ],
        ], [
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ]);

        $noAgt = array_key_exists('no_agt', $validated) ? $validated['no_agt'] : null;
        $idKelFromAnggota = null;
        if (is_string($noAgt) && trim($noAgt) !== '') {
            $idKelFromAnggota = Anggota::query()
                ->where('NO_AGT', trim($noAgt))
                ->value('ID_KS');
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'no_agt' => $noAgt,
            'id_kel' => $idKelFromAnggota,
            'is_active' => true,
            'registration_status' => User::REGISTRATION_APPROVED,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'User berhasil dibuat.',
            'data' => new UserResource($user),
        ], Response::HTTP_CREATED);
    }

    /**
     * Update user (role, is_active).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'role' => ['sometimes', 'string', Rule::in(['admin', 'user'])],
            'is_active' => ['sometimes', 'boolean'],
            'no_agt' => [
                'sometimes',
                'nullable',
                'string',
                'max:15',
                'exists:anggota,NO_AGT',
                Rule::unique('users', 'no_agt')->ignore($user->id),
            ],
            'id_kel' => [
                'sometimes',
                'nullable',
                'string',
                'max:12',
                'exists:kel_sah,ID_KEL',
            ],
        ]);

        if (array_key_exists('role', $validated)) {
            $user->role = $validated['role'];
        }
        if (array_key_exists('is_active', $validated)) {
            $user->is_active = $validated['is_active'];
            // Super admin: aktif/nonaktif langsung dari Manajemen Pengguna; saat diaktifkan, status pendaftaran disamakan approved.
            if ($validated['is_active'] === true) {
                $user->registration_status = User::REGISTRATION_APPROVED;
            }
        }
        if (array_key_exists('no_agt', $validated)) {
            $user->no_agt = $validated['no_agt'];
        }
        if (array_key_exists('id_kel', $validated)) {
            $user->id_kel = $validated['id_kel'];
        }
        $user->save();

        return response()->json([
            'message' => 'User berhasil diubah.',
            'data' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Delete user.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $actor = $request->user();

        if ($actor && (int) $user->id === (int) $actor->id) {
            return response()->json([
                'message' => 'Tidak dapat menghapus akun yang sedang login.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($user->role === 'super_admin') {
            return response()->json([
                'message' => 'Akun super admin tidak boleh dihapus.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->delete();

        return response()->json([
            'message' => 'User berhasil dihapus.',
        ]);
    }

    /**
     * Reset device_id user (super admin).
     */
    public function resetDevice(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $user->update([
            'device_id' => null,
        ]);

        return response()->json([
            'message' => 'Perangkat user berhasil direset.',
            'data' => new UserResource($user->fresh()),
        ], Response::HTTP_OK);
    }
}
