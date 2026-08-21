<?php

namespace App\Http\Controllers;

use App\Models\Spk;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class SuratPengantarController extends Controller
{
    public function show(Spk $spk): Response
    {
        /** @var User $user */
        $user = Auth::user();

        abort_unless(
            $user->isAdmin() || $spk->dikerjakanOleh()->where('by_user_id', $user->id)->exists(),
            403
        );

        $spk->load([
            'rambuPasang.rambu.jenisRambu',
            'dikerjakanOleh.user',
            'pembuat',
            'contactPerson',
        ]);

        $pdf = Pdf::loadView('pdf.surat-pengantar', ['spk' => $spk]);

        $filename = 'surat-pengantar-'.str_replace('/', '-', $spk->nomor_surat).'.pdf';

        // stream() (inline) instead of download() (attachment) so the link's
        // target="_blank" opens the PDF in the browser's own viewer first —
        // the user previews it there and downloads manually if they want to,
        // rather than a file landing straight in their Downloads folder.
        return $pdf->stream($filename);
    }
}
