<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $regionSeederName = 'RegionSeeder';
        $citySeederName = 'CitySeeder';

        // Charger les données depuis all.json
        $jsonPath = base_path('database/data/cities/ma/all.json');
        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        // Traiter les régions
        if (!DB::table('seeder_logs')->where('seeder_name', $regionSeederName)->exists()) {
            foreach ($data['regions']['data'] as $regionData) {
                Region::create([
                    'id' => $regionData['id'],
                    'name_ar' => $regionData['names']['ar'],
                    'name_en' => $regionData['names']['en'],
                    'name_fr' => $regionData['names']['fr'],
                ]);
            }

            DB::table('seeder_logs')->insert([
                'seeder_name' => $regionSeederName,
                'executed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('Régions importées avec succès!');
        } else {
            $this->command->warn("Seeder '{$regionSeederName}' already executed. Skipping...");
        }

        // Traiter les villes
        if (!DB::table('seeder_logs')->where('seeder_name', $citySeederName)->exists()) {
            foreach ($data['cities']['data'] as $cityData) {
                City::create([
                    'region_id' => $cityData['region_id'],
                    'name_ar' => $cityData['names']['ar'],
                    'name_en' => $cityData['names']['en'],
                    'name_fr' => $cityData['names']['fr'],
                ]);
            }

            DB::table('seeder_logs')->insert([
                'seeder_name' => $citySeederName,
                'executed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('Villes importées avec succès!');
        } else {
            $this->command->warn("Seeder '{$citySeederName}' already executed. Skipping...");
        }
    }
}
