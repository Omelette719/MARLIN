<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('contact_person')]
#[Fillable(['nama_lengkap', 'no_telepon', 'contact_person_spk_id'])]
class ContactPerson extends Model
{
    const UPDATED_AT = null;

    public function spk(): BelongsTo
    {
        return $this->belongsTo(Spk::class, 'contact_person_spk_id');
    }
}
