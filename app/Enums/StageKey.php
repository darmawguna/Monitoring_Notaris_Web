<?php

namespace App\Enums;

enum StageKey: string
{
    case FRONT_OFFICE = 'front_office';
    case PETUGAS_2 = 'petugas_2';
    case PAJAK = 'pajak';
    case PETUGAS_5 = 'petugas_5';
    case SELESAI = 'selesai';
}
