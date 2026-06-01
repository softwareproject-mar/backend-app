<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    public const REGISTRATION_PENDING = 'pending';

    public const REGISTRATION_APPROVED = 'approved';

    public const REGISTRATION_REJECTED = 'rejected';

    protected $fillable = [
        'name',
        'email',
        'no_agt',
        'id_kel',
        'device_id',
        'password',
        'role',
        'is_active',
        'registration_status',
        'registration_reviewed_at',
        'registration_reviewed_by',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'registration_reviewed_at' => 'datetime',
        ];
    }

    public function registrationReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registration_reviewed_by');
    }

    public function kelSah(): BelongsTo
    {
        return $this->belongsTo(KelSah::class, 'id_kel', 'ID_KEL');
    }

    /**
     * Firebird CHAR columns often pad with spaces — trim supaya role ("user"/admin) konsisten untuk scope API.
     */
    protected function role(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => is_string($value) ? trim($value) : $value,
            set: fn (mixed $value) => is_string($value) ? trim($value) : $value,
        );
    }
}
