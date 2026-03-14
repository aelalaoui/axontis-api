<?php

namespace Database\Seeders;

use App\Enums\DeviceCategory;
use App\Models\Device;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seederName = 'DeviceSeeder';

        if (!DB::table('seeder_logs')->where('seeder_name', $seederName)->exists()) {
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

            $this->command->info('Appareils Hikvision importés avec succès!');
        } else {
            $this->command->warn("Seeder '{$seederName}' already executed. Skipping...");
        }
    }
}

