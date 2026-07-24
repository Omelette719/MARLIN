<?php

namespace App\Models;

use App\Enums\ErrorLevel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('system_error_log')]
#[Fillable(['level', 'pesan', 'detail', 'endpoint', 'user_id'])]
class SystemErrorLog extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'level' => ErrorLevel::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
