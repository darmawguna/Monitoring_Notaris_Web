<?php

namespace App\Filament\Resources\Berkas\Schemas;

use App\Enums\BerkasStatus;
use App\Enums\StageKey;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class BerkasForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nomor')
                    ->required(),
                TextInput::make('nama_berkas')
                    ->required(),
                TextInput::make('penjual')
                    ->required(),
                TextInput::make('pembeli')
                    ->required(),
                TextInput::make('sertifikat_nama'),
                Textarea::make('persetujuan')
                    ->columnSpanFull(),
                TextInput::make('total_cost')
                    ->required()
                    ->numeric(),
                TextInput::make('total_paid')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Select::make('status_overall')
                    ->options(BerkasStatus::class)
                    ->required(),
                Select::make('current_stage_key')
                    ->options(StageKey::class)
                    ->required(),
                Select::make('current_assignee_id')
                    ->relationship('currentAssignee', 'name'),
                DateTimePicker::make('deadline_at'),
                TextInput::make('created_by')
                    ->required()
                    ->numeric(),
            ]);
    }
}
