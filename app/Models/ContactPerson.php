<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('rt_perwakilan')]
#[Fillable(['nama_lengkap', 'no_telepon', 'rtperwakilan_spk_id'])]
class RtPerwakilan extends Model
{
    const UPDATED_AT = null;

    public function spk(): BelongsTo
    {
        return $this->belongsTo(Spk::class, 'rtperwakilan_spk_id');
    }
}
