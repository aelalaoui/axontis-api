<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seederName = 'SupplierSeeder';

        if (!DB::table('seeder_logs')->where('seeder_name', $seederName)->exists()) {
            $suppliers = [
                [
                    'name' => 'Aziz MAANAOUI',
                    'code' => 'INTERN_AZIZ',
                    'email' => 'a.maanaoui@axontis.com',
                    'phone' => '+212-660-10-95-89',
                    'address' => null,
                    'city' => 'Casablanca',
                    'postal_code' => '21190',
                    'country' => 'Maroc',
                    'contact_person' => 'Aziz Maanaoui',
                    'website' => 'https://axontis.com',
                    'notes' => null,
                    'is_active' => true,
                ],
                [
                    'name' => 'Adil ALAOUI',
                    'code' => 'INTERN_ADIL',
                    'email' => 'a.alaoui@axontis.com',
                    'phone' => '+33-745-41-86-56',
                    'address' => null,
                    'city' => 'Nancy',
                    'postal_code' => '54000',
                    'country' => 'France',
                    'contact_person' => 'Adil ALAOUI',
                    'website' => 'https://axontis.com',
                    'notes' => null,
                    'is_active' => true,
                ],
            ];

            foreach ($suppliers as $supplierData) {
                Supplier::create($supplierData);
            }

            DB::table('seeder_logs')->insert([
                'seeder_name' => $seederName,
                'executed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('Fournisseurs importés avec succès!');
        } else {
            $this->command->warn("Seeder '{$seederName}' already executed. Skipping...");
        }
    }
}
