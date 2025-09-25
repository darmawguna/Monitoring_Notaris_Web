<?php

namespace App\Enums;

enum PembayaranStatus: string
{
    case LUNAS = 'lunas';
    case BELUM_LUNAS = 'belum_lunas';
}