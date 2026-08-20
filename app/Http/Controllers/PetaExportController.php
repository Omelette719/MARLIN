<?php

namespace App\Http\Controllers;

use App\Models\JenisRambu;
use App\Support\PetaData;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PetaExportController extends Controller
{
    public function export(Request $request): Response
    {
        $filters = [
            'jenis_rambu_id' => $request->query('jenis_rambu_id') ?: null,
            'tingkat' => $request->query('tingkat') ?: null,
            'kelurahan' => $request->query('kelurahan') ?: null,
            'kecamatan' => $request->query('kecamatan') ?: null,
        ];

        $request->validate([
            'gambar_peta' => 'nullable|image|max:8192',
        ]);

        $data = PetaData::build($filters);

        $data['jenisRambuNama'] = $filters['jenis_rambu_id']
            ? JenisRambu::find($filters['jenis_rambu_id'])?->nama_jenis
            : null;

        // Sent by leaflet-image (client-side canvas capture of the live map) —
        // dompdf can't render an interactive Leaflet map itself, so the browser
        // rasterizes what's currently on screen and uploads it here. Embedded
        // as a data URI straight from the upload; nothing is ever stored on disk.
        $gambarPeta = $request->file('gambar_peta');
        $data['gambarPetaDataUri'] = $gambarPeta
            ? 'data:'.$gambarPeta->getMimeType().';base64,'.base64_encode($gambarPeta->get())
            : null;

        $pdf = Pdf::loadView('pdf.peta-rambu', $data);

        // stream() (inline) instead of download() — the client (app.js's
        // kirimPetaPdf) opens the response blob in a new tab rather than
        // forcing a save-as, so this mirrors that on the server side too;
        // matters for the no-mapInstance fallback which navigates here
        // directly instead of going through the blob dance.
        return $pdf->stream('peta-rambu-'.now()->format('Y-m-d').'.pdf');
    }
}
