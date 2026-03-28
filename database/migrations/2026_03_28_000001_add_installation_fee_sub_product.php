<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds an "Installation Technicien" sub-product under BOTH Pack Business and Pack Particular.
     * The caution_price_cents (50 000 = 500 DH) is the fee charged to the client.
     * property_name = 'installation_mode' / default_value = 'technician'
     */
    public function up(): void
    {
        $now = now();

        $parents = DB::table('products')
            ->whereNull('id_parent')
            ->whereIn('name', ['Pack Business', 'Pack Particular'])
            ->get(['id', 'name']);

        foreach ($parents as $parent) {
            DB::table('products')->insert([
                'id'                       => Str::uuid()->toString(),
                'id_parent'                => $parent->id,
                'name'                     => 'Installation Technicien',
                'property_name'            => 'installation_mode',
                'default_value'            => 'technician',
                'caution_price_cents'      => 50000,  // 500.00 DH
                'subscription_price_cents' => null,
                'device_uuid'              => null,
                'created_at'               => $now,
                'updated_at'               => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('products')
            ->where('property_name', 'installation_mode')
            ->where('default_value', 'technician')
            ->where('name', 'Installation Technicien')
            ->delete();
    }
};

