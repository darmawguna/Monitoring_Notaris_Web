<?php

namespace Database\Seeders;

// use App\Enums\StageKey;
use App\Enums\StageKey;
use App\Models\DeadlineConfig;
use Illuminate\Database\Seeder;

class DeadlineConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = [
            ['stage_key' => StageKey::PETUGAS_ENTRY, 'default_days' => 2],
            ['stage_key' => StageKey::PETUGAS_PENGETIKAN, 'default_days' => 3],
            ['stage_key' => StageKey::PETUGAS_PAJAK, 'default_days' => 5],
            ['stage_key' => StageKey::PETUGAS_PENYIAPAN, 'default_days' => 3],
        ];

        foreach ($configs as $config) {
            DeadlineConfig::firstOrCreate(['stage_key' => $config['stage_key']], $config);
        }
    }
}