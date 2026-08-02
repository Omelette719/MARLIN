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
            'tanggal_dari' => $request->query('tanggal_dari') ?: null,
            'tanggal_sampai' => $request->query('tanggal_sampai') ?: null,
        ];

        $data = PetaData::build($filters);

        $data['jenisRambuNama'] = $filters['jenis_rambu_id']
            ? JenisRambu::find($filters['jenis_rambu_id'])?->nama_jenis
            : null;

        $pdf = Pdf::loadView('pdf.peta-rambu', $data);

        return $pdf->download('peta-rambu-'.now()->format('Y-m-d').'.pdf');
    }
}
