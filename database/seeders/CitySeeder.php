<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $seederName = 'CitySeeder';

        // Vérifier si ce seeder a déjà été exécuté
        $alreadySeeded = DB::table('seeder_logs')
            ->where('seeder_name', $seederName)
            ->exists();

        if ($alreadySeeded) {
            $this->command->warn("Seeder '{$seederName}' already executed. Skipping...");
            return;
        }

        $filePath = database_path('data/cities/ma/MA.txt');

        if (!File::exists($filePath)) {
            $this->command->error('Le fichier MA.txt n\'existe pas!');
            return;
        }

        $lines = File::lines($filePath);
        $cities = [];
        $batchSize = 500;

        foreach ($lines as $line) {
            $columns = explode("\t", $line);

            if (count($columns) < 7) {
                continue;
            }

            $cities[] = [
                'country_code' => $columns[0],
                'postal_code' => $columns[1],
                'city' => $columns[2],
                'region' => $columns[3],
                'prefecture' => $columns[5],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($cities) >= $batchSize) {
                City::insert($cities);
                $cities = [];
            }
        }

        if (!empty($cities)) {
            City::insert($cities);
        }

        // Enregistrer l'exécution du seeder
        DB::table('seeder_logs')->insert([
            'seeder_name' => $seederName,
            'executed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('Villes importées avec succès!');
    }
}
