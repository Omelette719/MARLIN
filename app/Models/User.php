<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Kelamin;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable([
    'name', 'nama_panggilan', 'nip', 'username', 'role',
    'tanggal_lahir', 'jenis_kelamin', 'bidang', 'jabatan', 'no_telepon', 'aktif',
    'password', 'telegram_chat_id', 'telegram_link_token', 'failed_login_attempts',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    // Deactivating an account (whether by an admin or by the automatic
    // failed-login lockout in FortifyServiceProvider) must not wait for the
    // user's existing session to expire on its own — session rows are
    // deleted immediately so their very next request gets logged out, and
    // remember_token is rotated so a "remember me" cookie can't silently
    // re-authenticate them either. Reactivating clears failed_login_attempts
    // back to zero so the account isn't one bad password away from being
    // immediately re-locked right after an admin re-enables it.
    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if (! $user->isDirty('aktif')) {
                return;
            }

            if ($user->aktif) {
                $user->failed_login_attempts = 0;
            } else {
                $user->remember_token = Str::random(60);
            }
        });

        static::updated(function (User $user) {
            if ($user->wasChanged('aktif') && ! $user->aktif) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => Role::class,
            'jenis_kelamin' => Kelamin::class,
            'tanggal_lahir' => 'date',
            'aktif' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function spkDibuat(): HasMany
    {
        return $this->hasMany(Spk::class, 'dibuat_oleh');
    }

    public function spkDikerjakan(): BelongsToMany
    {
        return $this->belongsToMany(Spk::class, 'dikerjakan_oleh', 'by_user_id', 'by_spk_id')
            ->withPivot('is_perwakilan');
    }

    public function laporanPengerjaan(): HasMany
    {
        return $this->hasMany(LaporanPengerjaan::class, 'dilaporkan_oleh');
    }

    public function laporanDivalidasi(): HasMany
    {
        return $this->hasMany(LaporanPengerjaan::class, 'divalidasi_oleh');
    }

    public function kendala(): HasMany
    {
        return $this->hasMany(Kendala::class, 'dilaporkan_oleh');
    }

    public function laporanKondisi(): HasMany
    {
        return $this->hasMany(LaporanKondisi::class, 'dilaporkan_oleh');
    }

    public function laporanKondisiDitindaklanjuti(): HasMany
    {
        return $this->hasMany(LaporanKondisi::class, 'ditindaklanjuti_oleh');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class);
    }
}
