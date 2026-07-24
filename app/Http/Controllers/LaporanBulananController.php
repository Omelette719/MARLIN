<?php

namespace App\Http\Controllers;

use App\Support\LaporanBulanan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LaporanBulananController extends Controller
{
    public function export(Request $request): Response
    {
        $bulan = $request->query('bulan') ?: now()->format('Y-m');

        $data = LaporanBulanan::build($bulan);

        $pdf = Pdf::loadView('pdf.laporan-bulanan', $data);

        $filename = 'laporan-bulanan-'.$bulan.'.pdf';

        return $pdf->download($filename);
    }
}
