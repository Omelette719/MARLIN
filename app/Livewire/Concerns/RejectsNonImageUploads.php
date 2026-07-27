<?php

namespace App\Livewire\Concerns;

use Flux\Flux;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

// A non-image file (PDF, doc, etc.) still uploads to Livewire's temp storage
// and updates the property before validate()/submit() ever runs — and the
// live preview built on it (TemporaryUploadedFile::temporaryUrl()) throws
// for any non-previewable extension. Catching it here, in the generic
// `updated` lifecycle hook that fires the moment the upload lands, rejects
// it immediately instead of letting that crash happen. Works for nested/array
// properties too (e.g. "rambuItems.0.foto_survei") since Livewire passes the
// full dotted path.
trait RejectsNonImageUploads
{
    public function updated($name, $value = null): void
    {
        if (! $value instanceof TemporaryUploadedFile) {
            return;
        }

        if (str_starts_with((string) $value->getMimeType(), 'image/')) {
            return;
        }

        data_set($this, $name, null);
        $this->addError($name, 'File harus berupa gambar (JPG, PNG, dll).');

        Flux::toast(variant: 'danger', text: 'File yang dipilih bukan gambar. Hanya file gambar (JPG, PNG, GIF, WebP, dll) yang diterima.');
    }
}
