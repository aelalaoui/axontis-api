<?php

use App\Enums\DeviceCategory;
use App\Models\Device;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        // Create Business Pack
        $idBusiness = Str::uuid()->toString();
        DB::table('products')->insert([
            'id' => $idBusiness,
            'name' => 'Pack Business',
            'property_name' => 'customerType',
            'default_value' => 'business',
            'caution_price_cents' => null,
            'subscription_price_cents' => null,
            'device_uuid' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Create Individual Pack
        $idIndividual = Str::uuid()->toString();
        DB::table('products')->insert([
            'id' => $idIndividual,
            'name' => 'Pack Particulier',
            'property_name' => 'customerType',
            'default_value' => 'individual',
            'caution_price_cents' => null,
            'subscription_price_cents' => null,
            'device_uuid' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Get device UUIDs
        $alarmPanel = Device::ByCategory(DeviceCategory::ALARM_PANEL->value)
            ->where('model', 'DS-PWA64-L-WB')
            ->first();
        $magneticSensor = Device::ByCategory(DeviceCategory::MAGNETIC_SENSOR->value)
            ->where('model', 'DS-PDMCX-E-WE')
            ->first();
        $brisGlass = Device::ByCategory(DeviceCategory::BRIS_GLASS->value)
            ->where('model', 'DS-PDBG8-EG2-WE')
            ->first();
        $smokeSenosr = Device::ByCategory(DeviceCategory::SMOCK_SENSOR->value)
            ->where('model', 'DS-PDMCK-EG2-WE(B)')
            ->first();
        $floodSensor = Device::ByCategory(DeviceCategory::FLOOD_SENSOR->value)
            ->where('model', 'DS-PDWL-E-WE')
            ->first();
        $repeter = Device::ByCategory(DeviceCategory::REPETER->value)
            ->where('model', 'DS-PR1-WE(B)')
            ->first();

        $businessProducts = [
            // Alarm Panels
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'alarm panel',
                'property_name' => 'propertyType',
                'default_value' => 'bureau',
                'caution_price_cents' => 170000,
                'subscription_price_cents' => 17000,
                'device_uuid' => $alarmPanel?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'alarm panel',
                'property_name' => 'propertyType',
                'default_value' => 'entrepot',
                'caution_price_cents' => 180000,
                'subscription_price_cents' => 16000,
                'device_uuid' => $alarmPanel?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'alarm panel',
                'property_name' => 'propertyType',
                'default_value' => 'magasin',
                'caution_price_cents' => 160000,
                'subscription_price_cents' => 15000,
                'device_uuid' => $alarmPanel?->uuid,
            ],
            // Magnetic Sensors
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'magnetic sensor',
                'property_name' => 'mainDoors',
                'default_value' => 1,
                'caution_price_cents' => 20000,
                'subscription_price_cents' => 5000,
                'device_uuid' => $magneticSensor?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'magnetic sensor',
                'property_name' => 'mainDoors',
                'default_value' => 2,
                'caution_price_cents' => 40000,
                'subscription_price_cents' => 5000,
                'device_uuid' => $magneticSensor?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'magnetic sensor',
                'property_name' => 'mainDoors',
                'default_value' => 3,
                'caution_price_cents' => 60000,
                'subscription_price_cents' => 7500,
                'device_uuid' => $magneticSensor?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'magnetic sensor',
                'property_name' => 'mainDoors',
                'default_value' => 4,
                'caution_price_cents' => 80000,
                'subscription_price_cents' => 10000,
                'device_uuid' => $magneticSensor?->uuid,
            ],
            // Auxiliary Entries
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Entrées Auxiliaires',
                'property_name' => 'auxiliaryEntries',
                'default_value' => 1,
                'caution_price_cents' => 20000,
                'subscription_price_cents' => 2000,
                'device_uuid' => $brisGlass?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Entrées Auxiliaires',
                'property_name' => 'auxiliaryEntries',
                'default_value' => 2,
                'caution_price_cents' => 40000,
                'subscription_price_cents' => 4000,
                'device_uuid' => $brisGlass?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Entrées Auxiliaires',
                'property_name' => 'auxiliaryEntries',
                'default_value' => 3,
                'caution_price_cents' => 60000,
                'subscription_price_cents' => 6000,
                'device_uuid' => $brisGlass?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Entrées Auxiliaires',
                'property_name' => 'auxiliaryEntries',
                'default_value' => 4,
                'caution_price_cents' => 80000,
                'subscription_price_cents' => 8000,
                'device_uuid' => $brisGlass?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Entrées Auxiliaires',
                'property_name' => 'auxiliaryEntries',
                'default_value' => 5,
                'caution_price_cents' => 100000,
                'subscription_price_cents' => 10000,
                'device_uuid' => $brisGlass?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Entrées Auxiliaires',
                'property_name' => 'auxiliaryEntries',
                'default_value' => 6,
                'caution_price_cents' => 120000,
                'subscription_price_cents' => 12000,
                'device_uuid' => $brisGlass?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Entrées Auxiliaires',
                'property_name' => 'auxiliaryEntries',
                'default_value' => 7,
                'caution_price_cents' => 140000,
                'subscription_price_cents' => 14000,
                'device_uuid' => $brisGlass?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Entrées Auxiliaires',
                'property_name' => 'auxiliaryEntries',
                'default_value' => 8,
                'caution_price_cents' => 160000,
                'subscription_price_cents' => 16000,
                'device_uuid' => $brisGlass?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Entrées Auxiliaires',
                'property_name' => 'auxiliaryEntries',
                'default_value' => 9,
                'caution_price_cents' => 180000,
                'subscription_price_cents' => 18000,
                'device_uuid' => $brisGlass?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Entrées Auxiliaires',
                'property_name' => 'auxiliaryEntries',
                'default_value' => 10,
                'caution_price_cents' => 200000,
                'subscription_price_cents' => 20000,
                'device_uuid' => $brisGlass?->uuid,
            ],
            // Fire Sensors
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Détécteur de Fumée',
                'property_name' => 'fireSensors',
                'default_value' => 1,
                'caution_price_cents' => 20000,
                'subscription_price_cents' => 2000,
                'device_uuid' => $smokeSenosr?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Détécteur de Fumée',
                'property_name' => 'fireSensors',
                'default_value' => 2,
                'caution_price_cents' => 40000,
                'subscription_price_cents' => 4000,
                'device_uuid' => $smokeSenosr?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Détécteur de Fumée',
                'property_name' => 'fireSensors',
                'default_value' => 3,
                'caution_price_cents' => 60000,
                'subscription_price_cents' => 6000,
                'device_uuid' => $smokeSenosr?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Détécteur de Fumée',
                'property_name' => 'fireSensors',
                'default_value' => 4,
                'caution_price_cents' => 80000,
                'subscription_price_cents' => 8000,
                'device_uuid' => $smokeSenosr?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Détécteur de Fumée',
                'property_name' => 'fireSensors',
                'default_value' => 5,
                'caution_price_cents' => 100000,
                'subscription_price_cents' => 10000,
                'device_uuid' => $smokeSenosr?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Détécteur de Fumée',
                'property_name' => 'fireSensors',
                'default_value' => 6,
                'caution_price_cents' => 120000,
                'subscription_price_cents' => 12000,
                'device_uuid' => $smokeSenosr?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Détécteur de Fumée',
                'property_name' => 'fireSensors',
                'default_value' => 7,
                'caution_price_cents' => 140000,
                'subscription_price_cents' => 14000,
                'device_uuid' => $smokeSenosr?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Détécteur de Fumée',
                'property_name' => 'fireSensors',
                'default_value' => 8,
                'caution_price_cents' => 160000,
                'subscription_price_cents' => 16000,
                'device_uuid' => $smokeSenosr?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Détécteur de Fumée',
                'property_name' => 'fireSensors',
                'default_value' => 9,
                'caution_price_cents' => 180000,
                'subscription_price_cents' => 18000,
                'device_uuid' => $smokeSenosr?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Détécteur de Fumée',
                'property_name' => 'fireSensors',
                'default_value' => 10,
                'caution_price_cents' => 200000,
                'subscription_price_cents' => 20000,
                'device_uuid' => $smokeSenosr?->uuid,
            ],
            // Flood Sensors
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Détécteur d\'innondation',
                'property_name' => 'floodSensors',
                'default_value' => 1,
                'caution_price_cents' => 20000,
                'subscription_price_cents' => 2000,
                'device_uuid' => $floodSensor?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Détécteur d\'innondation',
                'property_name' => 'floodSensors',
                'default_value' => 2,
                'caution_price_cents' => 40000,
                'subscription_price_cents' => 4000,
                'device_uuid' => $floodSensor?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Détécteur d\'innondation',
                'property_name' => 'floodSensors',
                'default_value' => 3,
                'caution_price_cents' => 60000,
                'subscription_price_cents' => 6000,
                'device_uuid' => $floodSensor?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Détécteur d\'innondation',
                'property_name' => 'floodSensors',
                'default_value' => 4,
                'caution_price_cents' => 80000,
                'subscription_price_cents' => 8000,
                'device_uuid' => $floodSensor?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Détécteur d\'innondation',
                'property_name' => 'floodSensors',
                'default_value' => 5,
                'caution_price_cents' => 100000,
                'subscription_price_cents' => 10000,
                'device_uuid' => $floodSensor?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Détécteur d\'innondation',
                'property_name' => 'floodSensors',
                'default_value' => 6,
                'caution_price_cents' => 120000,
                'subscription_price_cents' => 12000,
                'device_uuid' => $floodSensor?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Détécteur d\'innondation',
                'property_name' => 'floodSensors',
                'default_value' => 7,
                'caution_price_cents' => 140000,
                'subscription_price_cents' => 14000,
                'device_uuid' => $floodSensor?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Détécteur d\'innondation',
                'property_name' => 'floodSensors',
                'default_value' => 8,
                'caution_price_cents' => 160000,
                'subscription_price_cents' => 16000,
                'device_uuid' => $floodSensor?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Détécteur d\'innondation',
                'property_name' => 'floodSensors',
                'default_value' => 9,
                'caution_price_cents' => 180000,
                'subscription_price_cents' => 18000,
                'device_uuid' => $floodSensor?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Détécteur d\'innondation',
                'property_name' => 'floodSensors',
                'default_value' => 10,
                'caution_price_cents' => 200000,
                'subscription_price_cents' => 20000,
                'device_uuid' => $floodSensor?->uuid,
            ],
            // Repeaters
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Répéteur',
                'property_name' => 'propertySize',
                'default_value' => '0-100',
                'caution_price_cents' => null,
                'subscription_price_cents' => null,
                'device_uuid' => $repeter?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Répéteur',
                'property_name' => 'propertySize',
                'default_value' => '100-200',
                'caution_price_cents' => null,
                'subscription_price_cents' => null,
                'device_uuid' => $repeter?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Répéteur',
                'property_name' => 'propertySize',
                'default_value' => '200-300',
                'caution_price_cents' => 120000,
                'subscription_price_cents' => 5000,
                'device_uuid' => $repeter?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Répéteur',
                'property_name' => 'propertySize',
                'default_value' => '300-400',
                'caution_price_cents' => 120000,
                'subscription_price_cents' => 8000,
                'device_uuid' => $repeter?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Répéteur',
                'property_name' => 'propertySize',
                'default_value' => '400-500',
                'caution_price_cents' => 240000,
                'subscription_price_cents' => 10000,
                'device_uuid' => $repeter?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Répéteur',
                'property_name' => 'propertySize',
                'default_value' => '500-1000',
                'caution_price_cents' => 360000,
                'subscription_price_cents' => 15000,
                'device_uuid' => $repeter?->uuid,
            ],
            // Wifi
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Wifi',
                'property_name' => 'hasWifi',
                'default_value' => 'oui',
                'caution_price_cents' => null,
                'subscription_price_cents' => null,
                'device_uuid' => null,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idBusiness,
                'name' => 'Wifi',
                'property_name' => 'hasWifi',
                'default_value' => 'non',
                'caution_price_cents' => null,
                'subscription_price_cents' => 5000,
                'device_uuid' => null,
            ],
        ];

        $individualProducts = [
            // Alarm Panels
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idIndividual,
                'name' => 'alarm panel',
                'property_name' => 'propertyType',
                'default_value' => 'villa',
                'caution_price_cents' => 200000,
                'subscription_price_cents' => 20000,
                'device_uuid' => $alarmPanel?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idIndividual,
                'name' => 'alarm panel',
                'property_name' => 'propertyType',
                'default_value' => 'maison',
                'caution_price_cents' => 180000,
                'subscription_price_cents' => 16000,
                'device_uuid' => $alarmPanel?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idIndividual,
                'name' => 'alarm panel',
                'property_name' => 'propertyType',
                'default_value' => 'appartement',
                'caution_price_cents' => 160000,
                'subscription_price_cents' => 14000,
                'device_uuid' => $alarmPanel?->uuid,
            ],
            // Magnetic Sensors
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idIndividual,
                'name' => 'magnetic sensor',
                'property_name' => 'mainDoors',
                'default_value' => 1,
                'caution_price_cents' => 20000,
                'subscription_price_cents' => 5000,
                'device_uuid' => $magneticSensor?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idIndividual,
                'name' => 'magnetic sensor',
                'property_name' => 'mainDoors',
                'default_value' => 2,
                'caution_price_cents' => 40000,
                'subscription_price_cents' => 5000,
                'device_uuid' => $magneticSensor?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idIndividual,
                'name' => 'magnetic sensor',
                'property_name' => 'mainDoors',
                'default_value' => 3,
                'caution_price_cents' => 60000,
                'subscription_price_cents' => 7500,
                'device_uuid' => $magneticSensor?->uuid,
            ],
            [
                'id' => Str::uuid()->toString(),
                'id_parent' => $idIndividual,
                'name' => 'magnetic sensor',
                'property_name' => 'mainDoors',
                'default_value' => 4,
                'caution_price_cents' => 80000,
                'subscription_price_cents' => 10000,
                'device_uuid' => $magneticSensor?->uuid,
            ],
        ];

        // Batch insert all products
        foreach ($businessProducts as $product) {
            $product['created_at'] = $now;
            $product['updated_at'] = $now;
            DB::table('products')->insert($product);
        }

        foreach ($individualProducts as $product) {
            $product['created_at'] = $now;
            $product['updated_at'] = $now;
            DB::table('products')->insert($product);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('products')
            ->where('name', 'Pack Business')
            ->where('property_name', 'customerType')
            ->where('default_value', 'business')
            ->delete();

        DB::table('products')
            ->where('name', 'Pack Particulier')
            ->where('property_name', 'customerType')
            ->where('default_value', 'individual')
            ->delete();
    }
};
