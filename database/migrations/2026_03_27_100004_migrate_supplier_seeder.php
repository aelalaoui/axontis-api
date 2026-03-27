<?php

use App\Models\Supplier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $seederName = 'SupplierSeeder';

        // Skip if already executed
        if (DB::table('seeder_logs')->where('seeder_name', $seederName)->exists()) {
            return;
        }

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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $seederName = 'SupplierSeeder';

        // Delete suppliers
        if (DB::table('seeder_logs')->where('seeder_name', $seederName)->exists()) {
            $codes = ['INTERN_AZIZ', 'INTERN_ADIL'];
            Supplier::whereIn('code', $codes)->delete();
        }

        // Remove seeder log
        DB::table('seeder_logs')
            ->where('seeder_name', $seederName)
            ->delete();
    }
};

