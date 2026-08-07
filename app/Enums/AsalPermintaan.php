<?php

namespace App\Enums;

enum AsalPermintaan: string
{
    case Internal = 'internal';
    case LaporanMasyarakat = 'laporan_masyarakat';
    case InstruksiPemerintah = 'instruksi_pemerintah';
    case EvaluasiPetugas = 'evaluasi_petugas';
    case ProgramKinerja = 'program_kinerja';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Internal',
            self::LaporanMasyarakat => 'Laporan Masyarakat',
            self::InstruksiPemerintah => 'Instruksi Pemerintah',
            self::EvaluasiPetugas => 'Evaluasi Petugas',
            self::ProgramKinerja => 'Program Kinerja',
        };
    }
}
