<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('dikerjakan_oleh')]
#[Fillable(['by_spk_id', 'by_user_id', 'is_perwakilan'])]
class DikerjakanOleh extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'is_perwakilan' => 'boolean',
        ];
    }

    public function spk(): BelongsTo
    {
        return $this->belongsTo(Spk::class, 'by_spk_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'by_user_id');
    }
}
