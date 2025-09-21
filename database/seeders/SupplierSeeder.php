<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'TechCorp Solutions',
                'code' => 'TECH001',
                'email' => 'contact@techcorp.com',
                'phone' => '+1-555-0123',
                'address' => '123 Technology Drive',
                'city' => 'San Francisco',
                'postal_code' => '94105',
                'country' => 'United States',
                'contact_person' => 'John Smith',
                'website' => 'https://techcorp.com',
                'notes' => 'Primary technology supplier for hardware components.',
                'is_active' => true,
            ],
            [
                'name' => 'Global Electronics Ltd',
                'code' => 'GLOB002',
                'email' => 'sales@globalelectronics.com',
                'phone' => '+44-20-7946-0958',
                'address' => '456 Electronics Avenue',
                'city' => 'London',
                'postal_code' => 'SW1A 1AA',
                'country' => 'United Kingdom',
                'contact_person' => 'Sarah Johnson',
                'website' => 'https://globalelectronics.com',
                'notes' => 'Reliable supplier for electronic components and accessories.',
                'is_active' => true,
            ],
            [
                'name' => 'Mobile Parts Express',
                'code' => 'MOB003',
                'email' => 'orders@mobileparts.com',
                'phone' => '+33-1-42-86-83-26',
                'address' => '789 Mobile Street',
                'city' => 'Paris',
                'postal_code' => '75001',
                'country' => 'France',
                'contact_person' => 'Pierre Dubois',
                'website' => 'https://mobileparts.com',
                'notes' => 'Specialized in mobile device parts and repairs.',
                'is_active' => true,
            ],
            [
                'name' => 'Inactive Supplier Co',
                'code' => 'INAC004',
                'email' => 'info@inactive.com',
                'phone' => '+1-555-0999',
                'address' => '999 Inactive Road',
                'city' => 'Nowhere',
                'postal_code' => '00000',
                'country' => 'United States',
                'contact_person' => 'Nobody',
                'website' => null,
                'notes' => 'This supplier is currently inactive.',
                'is_active' => false,
            ],
        ];

        foreach ($suppliers as $supplierData) {
            Supplier::create($supplierData);
        }
    }
}