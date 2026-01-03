<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = base_path('database/data/cities/all.json');
        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        foreach ($data['regions']['data'] as $regionData) {
            Region::create([
                'id' => $regionData['id'],
                'name_ar' => $regionData['names']['ar'],
                'name_en' => $regionData['names']['en'],
                'name_fr' => $regionData['names']['fr'],
            ]);
        }
    }
}

