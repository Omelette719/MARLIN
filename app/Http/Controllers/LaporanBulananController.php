<?php

namespace App\Http\Controllers;

use App\Models\JenisRambu;
use App\Support\LaporanBulanan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LaporanBulananController extends Controller
{
    public function export(Request $request): Response
    {
        $filters = [
            'tanggal_dari' => $request->query('tanggal_dari') ?: null,
            'tanggal_sampai' => $request->query('tanggal_sampai') ?: null,
            'jenis_rambu_id' => $request->query('jenis_rambu_id') ?: null,
            'status' => $request->query('status') ?: null,
        ];

        $data = LaporanBulanan::build($filters);

        $data['jenisRambuNama'] = $filters['jenis_rambu_id']
            ? JenisRambu::find($filters['jenis_rambu_id'])?->nama_jenis
            : null;

        $pdf = Pdf::loadView('pdf.laporan-bulanan', $data);

        $filename = 'laporan-bulanan-'.now()->format('Y-m-d').'.pdf';

        // stream() (inline), see SuratPengantarController for why.
        return $pdf->stream($filename);
    }
}
