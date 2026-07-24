<?php

namespace App\Http\Controllers;

use App\Enums\StatusRambuPasang;
use App\Models\Rambu;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class PetaController extends Controller
{
    public function data(): JsonResponse
    {
        $pins = Rambu::with([
            'jenisRambu',
            'rambuPasang' => fn ($q) => $q->latest()->with(['spk', 'laporanPengerjaan']),
        ])
            ->get()
            ->map(fn (Rambu $rambu) => $this->toPin($rambu))
            ->filter()
            ->values();

        return response()->json($pins);
    }

    private function toPin(Rambu $rambu): ?array
    {
        $task = $rambu->rambuPasang
            ->reject(fn ($t) => $t->status === StatusRambuPasang::Batal)
            ->first();

        if (! $task && ! $rambu->sudah_terpasang) {
            return null;
        }

        $coords = $this->parseKoordinat($rambu->koordinat);

        if (! $coords) {
            return null;
        }

        $foto = $rambu->fotoUtama();

        return [
            'id' => $rambu->id,
            'lat' => $coords[0],
            'lng' => $coords[1],
            'wilayah' => $rambu->wilayah,
            'lokasi' => $rambu->lokasi,
            'jenis_rambu' => $rambu->jenisRambu?->nama_jenis,
            'ikon' => $rambu->jenisRambu?->gambar_referensi ? Storage::url($rambu->jenisRambu->gambar_referensi) : null,
            'foto' => $foto ? Storage::url($foto) : null,
            'bentuk_ikon' => $rambu->jenisRambu?->bentuk_ikon?->value ?? 'bulat',
            'kondisi_terkini' => $rambu->kondisi_terkini->value,
            'sudah_terpasang' => $rambu->sudah_terpasang,
            'status' => $task?->status->value,
            'jenis_pekerjaan' => $task?->jenis_pekerjaan->value,
            'spk' => $task?->spk ? [
                'id' => $task->spk->id,
                'nomor_surat' => $task->spk->nomor_surat,
                'prioritas' => $task->spk->prioritas,
                'urgensi' => $task->spk->urgensi->value,
                'deadline' => $task->spk->deadline->format('Y-m-d'),
            ] : null,
        ];
    }

    private function parseKoordinat(?string $koordinat): ?array
    {
        if (! $koordinat || ! str_contains($koordinat, ',')) {
            return null;
        }

        [$lat, $lng] = array_map('trim', explode(',', $koordinat, 2));

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return [(float) $lat, (float) $lng];
    }
}
