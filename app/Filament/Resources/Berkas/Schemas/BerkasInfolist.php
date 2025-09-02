<?php

namespace App\Filament\Resources\Berkas\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BerkasInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('nomor'),
                TextEntry::make('nama_berkas'),
                TextEntry::make('penjual'),
                TextEntry::make('pembeli'),
                TextEntry::make('sertifikat_nama'),
                TextEntry::make('total_cost')
                    ->numeric(),
                TextEntry::make('total_paid')
                    ->numeric(),
                TextEntry::make('status_overall'),
                TextEntry::make('current_stage_key'),
                TextEntry::make('currentAssignee.name'),
                TextEntry::make('deadline_at')
                    ->dateTime(),
                TextEntry::make('created_by')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
