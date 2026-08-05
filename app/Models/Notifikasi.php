<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('notifikasi')]
#[Fillable(['user_id', 'judul', 'pesan', 'url', 'foto', 'dibaca'])]
class Notifikasi extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'dibaca' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
