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
    // Property names allowed to also accept a PDF on top of images — for
    // scanned documents (e.g. Spk's file_referensi) rather than field photos.
    // None of these fields feed a live preview via temporaryUrl(), so a PDF
    // here never hits the crash this trait exists to prevent. A method
    // (not a property) so using classes can override it without a PHP
    // trait/class property-collision error.
    protected function pdfAllowedFields(): array
    {
        return [];
    }

    public function updated($name, $value = null): void
    {
        if (! $value instanceof TemporaryUploadedFile) {
            return;
        }

        $mime = (string) $value->getMimeType();

        if (str_starts_with($mime, 'image/')) {
            return;
        }

        $bolehPdf = in_array($name, $this->pdfAllowedFields(), true);

        if ($bolehPdf && $mime === 'application/pdf') {
            return;
        }

        data_set($this, $name, null);
        $this->addError($name, $bolehPdf ? 'File harus berupa gambar atau PDF.' : 'File harus berupa gambar (JPG, PNG, dll).');

        Flux::toast(variant: 'danger', text: $bolehPdf
            ? 'File yang dipilih bukan gambar/PDF. Hanya file gambar atau PDF yang diterima.'
            : 'File yang dipilih bukan gambar. Hanya file gambar (JPG, PNG, GIF, WebP, dll) yang diterima.');
    }
}
