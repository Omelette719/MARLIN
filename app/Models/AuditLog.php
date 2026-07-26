<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('audit_log')]
#[Fillable(['user_id', 'spk_id', 'aksi', 'tabel_terkait', 'record_id_terkait', 'keterangan'])]
class AuditLog extends Model
{
    const UPDATED_AT = null;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function spk(): BelongsTo
    {
        return $this->belongsTo(Spk::class);
    }

    /**
     * Scope audit log rows to what the given user is allowed to see:
     * admins see everything, petugas only see their own actions.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }
}
