<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * ISO8601 aman: beberapa driver mengembalikan datetime sebagai string/int;
     * nilai aneh tidak boleh memutus seluruh response (HTTP 500 pada list user).
     */
    private function optionalIso8601(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if ($value instanceof CarbonInterface) {
                return $value->toISOString();
            }

            if (is_int($value) || is_float($value)) {
                if ((float) $value <= 0.0) {
                    return null;
                }

                return Carbon::createFromTimestamp((int) $value)->utc()->toISOString();
            }

            if (is_string($value)) {
                $trim = trim($value);

                return $trim === '' ? null : Carbon::parse($trim)->utc()->toISOString();
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'no_agt' => $this->no_agt,
            'jabatan' => $this->jabatan,
            'id_kel' => $this->id_kel,
            'device_id' => $this->device_id,
            'nama_kelompok_sahabat' => $this->whenLoaded(
                'kelSah',
                function () {
                    try {
                        $nama = $this->kelSah?->NAMA_KEL;

                        return is_string($nama) && trim($nama) !== ''
                            ? trim($nama)
                            : null;
                    } catch (\Throwable) {
                        return null;
                    }
                }
            ),
            'role' => $this->role,
            'is_active' => $this->is_active,
            'registration_status' => $this->registration_status,
            'registration_reviewed_at' => $this->optionalIso8601($this->registration_reviewed_at),
            'registration_reviewed_by' => $this->registration_reviewed_by === null
                ? null
                : (
                    $this->relationLoaded('registrationReviewer') && $this->registrationReviewer
                    ? [
                        'id' => $this->registrationReviewer->id,
                        'name' => $this->registrationReviewer->name,
                        'email' => $this->registrationReviewer->email,
                    ]
                    : [
                        'id' => $this->registration_reviewed_by,
                        'name' => null,
                        'email' => null,
                    ]
                ),
            'last_login_at' => $this->optionalIso8601($this->last_login_at),
            'created_at' => $this->optionalIso8601($this->created_at),
            'updated_at' => $this->optionalIso8601($this->updated_at),
        ];
    }
}
