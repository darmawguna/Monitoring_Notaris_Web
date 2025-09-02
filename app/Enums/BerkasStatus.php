<?php

namespace App\Enums;

enum BerkasStatus: string
{
    case BARU = 'baru';
    case PROGRES = 'progres';
    case SELESAI = 'selesai';
    case REVISI = 'revisi';
}
