<?php

use App\Models\City;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $seederName = 'CitySeeder';

        // Skip if already executed
        if (DB::table('seeder_logs')->where('seeder_name', $seederName)->exists()) {
            return;
        }

        // Charger les données depuis all.json
        $jsonPath = base_path('database/data/cities/ma/all.json');
        if (!file_exists($jsonPath)) {
            return;
        }

        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        // Traiter les villes
        foreach ($data['cities']['data'] as $cityData) {
            City::create([
                'region_id' => $cityData['region_id'],
                'name_ar' => $cityData['names']['ar'],
                'name_en' => $cityData['names']['en'],
                'name_fr' => $cityData['names']['fr'],
            ]);
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
        $seederName = 'CitySeeder';

        // Delete cities
        if (DB::table('seeder_logs')->where('seeder_name', $seederName)->exists()) {
            City::query()->delete();
        }

        // Remove seeder log
        DB::table('seeder_logs')
            ->where('seeder_name', $seederName)
            ->delete();
    }
};

