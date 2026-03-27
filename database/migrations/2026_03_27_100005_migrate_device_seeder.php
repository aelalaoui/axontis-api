<?php

use App\Enums\DeviceCategory;
use App\Models\Device;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $seederName = 'DeviceSeeder';

        // Skip if already executed
        if (DB::table('seeder_logs')->where('seeder_name', $seederName)->exists()) {
            return;
        }

        $devices = [
            [
                'brand' => 'Hikvision',
                'model' => 'DS-PWA64-L-WB',
                'stock_qty' => 0,
                'category' => DeviceCategory::ALARM_PANEL->value,
                'description' => 'Central d\'alarm AX PRO',
                'min_stock_level' => 5,
            ],
            [
                'brand' => 'Hikvision',
                'model' => 'DS-PDPC12P-EG2-WE(B)',
                'stock_qty' => 0,
                'category' => DeviceCategory::MOTION_SENSOR->value,
                'description' => 'Detecteur de mouvement avec camera video interieur',
                'min_stock_level' => 5,
            ],
            [
                'brand' => 'Hikvision',
                'model' => 'DS-PDPC18-HM-WE',
                'stock_qty' => 0,
                'category' => DeviceCategory::MOTION_SENSOR->value,
                'description' => 'Detecteur de mouvement avec camera video exterieur',
                'min_stock_level' => 5,
            ],
            [
                'brand' => 'Hikvision',
                'model' => 'DS-PDMCX-E-WE',
                'stock_qty' => 0,
                'category' => DeviceCategory::MAGNETIC_SENSOR->value,
                'description' => 'Contact magnétique externe sans fil',
                'min_stock_level' => 5,
            ],
            [
                'brand' => 'Hikvision',
                'model' => 'DS-PDMCK-EG2-WE(B)',
                'stock_qty' => 0,
                'category' => DeviceCategory::SMOCK_SENSOR->value,
                'description' => 'Détecteur de fumée photoélectrique sans fil',
                'min_stock_level' => 5,
            ],
            [
                'brand' => 'Hikvision',
                'model' => 'DS-PS1-E-WE',
                'stock_qty' => 0,
                'category' => DeviceCategory::SIRENE->value,
                'description' => 'Sirène Extérieure sans fil',
                'min_stock_level' => 5,
            ],
            [
                'brand' => 'Hikvision',
                'model' => 'DS-PK1-E-WE',
                'stock_qty' => 0,
                'category' => DeviceCategory::KEYPAD->value,
                'description' => 'Wireless LED keypad',
                'min_stock_level' => 5,
            ],
            [
                'brand' => 'Hikvision',
                'model' => 'DS-PDBG8-EG2-WE',
                'stock_qty' => 0,
                'category' => DeviceCategory::BRIS_GLASS->value,
                'description' => 'Détecteur de bris de verre sans fil',
                'min_stock_level' => 5,
            ],
            [
                'brand' => 'Hikvision',
                'model' => 'DS-PDWL-E-WE',
                'stock_qty' => 0,
                'category' => DeviceCategory::FLOOD_SENSOR->value,
                'description' => 'Détecteur de fuite d\'eau sans fil',
                'min_stock_level' => 5,
            ],
            [
                'brand' => 'Hikvision',
                'model' => 'DS-PR1-WE(B)',
                'stock_qty' => 0,
                'category' => DeviceCategory::REPETER->value,
                'description' => 'Répéteur sans fil Plus',
                'min_stock_level' => 5,
            ],
        ];

        foreach ($devices as $deviceData) {
            Device::create($deviceData);
        }

        DB::table('seeder_logs')->insert([
            'seeder_name' => $seederName,
            'executed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $seederName = 'DeviceSeeder';

        // Delete devices
        if (DB::table('seeder_logs')->where('seeder_name', $seederName)->exists()) {
            $models = [
                'DS-PWA64-L-WB',
                'DS-PDPC12P-EG2-WE(B)',
                'DS-PDPC18-HM-WE',
                'DS-PDMCX-E-WE',
                'DS-PDMCK-EG2-WE(B)',
                'DS-PS1-E-WE',
                'DS-PK1-E-WE',
                'DS-PDBG8-EG2-WE',
                'DS-PDWL-E-WE',
                'DS-PR1-WE(B)',
            ];
            Device::whereIn('model', $models)->delete();
        }

        // Remove seeder log
        DB::table('seeder_logs')
            ->where('seeder_name', $seederName)
            ->delete();
    }
};

