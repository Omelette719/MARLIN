<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('kendala')]
#[Fillable(['rambu_pasang_id', 'dilaporkan_oleh', 'alasan', 'foto'])]
class Kendala extends Model
{
    public function rambuPasang(): BelongsTo
    {
        return $this->belongsTo(RambuPasang::class);
    }

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dilaporkan_oleh');
    }
}
