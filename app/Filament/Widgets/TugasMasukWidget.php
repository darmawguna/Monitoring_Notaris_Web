<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * INI ADALAH BASE CLASS ABSTRAK
 * Jangan daftarkan widget ini secara langsung.
 * Widget lain akan mewarisi (extend) kelas ini.
 */
abstract class TugasMasukWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';
    protected static bool $isLazy = false;

    // Properti ini akan di-override oleh kelas anak
    protected static string $model;
    protected static string $title;

    public function getTableHeading(): string
    {
        return static::$title;
    }

    // Metode ini akan di-override oleh kelas anak untuk menyediakan URL yang benar
    abstract public static function getResource(): string;

    // Hanya tampilkan widget ini untuk petugas (bukan Superadmin atau Petugas Entry)
    public static function canView(): bool
    {
        $userRole = auth()->user()->role->name;
        return !in_array($userRole, ['Superadmin', 'Petugas Entry']);
    }

    protected function getTableQuery(): Builder
    {
        // Query ini akan mengambil record dari model anak (misal: Berkas, Perbankan)
        // yang memiliki tugas 'pending' yang ditugaskan kepada pengguna saat ini.
        return static::$model::query()
            ->whereHas('progress', function (Builder $query) {
                $query->where('assignee_id', auth()->id())
                    ->where('status', 'pending');
            })
            ->latest()
            ->limit(5);
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make()
                ->url(fn(Model $record): string => static::getResource()::getUrl('view', ['record' => $record])),
        ];
    }
}
