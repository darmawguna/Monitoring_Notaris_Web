<?php

namespace App\Enums;

enum StageKey: string
{
    case PETUGAS_ENTRY = 'petugas_entry';
    case PETUGAS_PENGETIKAN = 'petugas pengetikan';
    case PETUGAS_PAJAK = 'petugas pajak';
    case PETUGAS_PENYIAPAN = 'petugas penyiapan';
    case SELESAI = 'selesai';
}
