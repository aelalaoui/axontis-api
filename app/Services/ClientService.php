<?php

namespace App\Services;

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\Product;
use Exception;

class ClientService
{
    /**
     * Find or create a client by email
     * 
     * @param string $email
     * @param string $country
     * @return Client
     */
    public function findOrCreateClient(string $email, string $country): Client
    {
        // Check if client exists
        $client = Client::where('email', $email)->first();

        if ($client) {
            return $client;
        }

        // Create new client
        return Client::create([
            'email' => $email,
            'country' => $country,
            'type' => 'unknown',
            'status' => ClientStatus::EMAIL_STEP
        ]);
    }

    /**
     * Find a client by UUID
     * 
     * @param string $uuid
     * @return Client|null
     */
    public function findClientByUuid(string $uuid): ?Client
    {
        return Client::where('uuid', $uuid)->first();
    }

    /**
     * Update client status
     * 
     * @param Client $client
     * @param string $step
     * @return array Returns array with previous and current status
     */
    public function updateClientStatus(Client $client, string $step): array
    {
        $previousStatus = $client->status;
        $client->update(['status' => $step]);

        return [
            'uuid' => $client->uuid,
            'previous_status' => $previousStatus,
            'current_status' => $client->status
        ];
    }

    /**
     * Store client criterias as properties
     * 
     * @param Client $client
     * @param array $criterias
     * @return array Returns stored properties information
     */
    public function storeCriterias(Client $client, array $criterias): array
    {
        // Clear existing properties
        $client->clearProperties();

        // Store each criteria as a property
        $storedProperties = [];
        foreach ($criterias as $key => $value) {
            if ($key === 'email') {
                continue; // Ignore email
            }
            $propertyName = "{$key}";
            $storedProperty = $client->setProperty($propertyName, $value);
            $storedProperties[] = [
                'property' => $propertyName,
                'value' => $value,
                'type' => $storedProperty->type
            ];
        }

        // Update client type based on customerType property
        $client->type = $client->getProperty('customerType', 'unknown');
        $client->save();

        return [
            'client_uuid' => $client->uuid,
            'stored_criterias' => $storedProperties,
            'criterias_count' => count($storedProperties),
            'timestamp' => now()->toDateTimeString()
        ];
    }

    /**
     * Find parent product by property and value
     * 
     * @param string $property
     * @param string $value
     * @return Product|null
     */
    public function findParentProduct(string $property, string $value): ?Product
    {
        return Product::whereNull('id_parent')
            ->where('property_name', $property)
            ->where('default_value', $value)
            ->first();
    }

    /**
     * Get sub-products for a parent product
     * 
     * @param Product $parentProduct
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSubProducts(Product $parentProduct)
    {
        return Product::where('id_parent', $parentProduct->id)->get();
    }

    /**
     * Calculate offer prices based on client properties and products
     * 
     * @param Client $client
     * @param Product $parentProduct
     * @return array Returns calculation results
     */
    public function calculateOfferPrices(Client $client, Product $parentProduct): array
    {
        // Get all sub-products
        $subProducts = $this->getSubProducts($parentProduct);

        // Initialize totals
        $totalCautionPrice = 0;
        $totalSubscriptionPrice = 0;
        $matchedProducts = [];

        // For each sub-product, check if client has the corresponding property
        foreach ($subProducts as $subProduct) {
            if ($subProduct->property_name) {
                // Get client property value
                $clientPropertyValue = $client->getProperty($subProduct->property_name);

                // If client has this property and it matches the product's default value
                if ($clientPropertyValue !== null && $clientPropertyValue == $subProduct->default_value) {
                    $totalCautionPrice += $subProduct->caution_price ?? 0;
                    $totalSubscriptionPrice += $subProduct->subscription_price ?? 0;

                    $matchedProducts[] = [
                        'product_id' => $subProduct->id,
                        'product_name' => $subProduct->name,
                        'property_name' => $subProduct->property_name,
                        'client_property_value' => $clientPropertyValue,
                        'product_default_value' => $subProduct->default_value,
                        'caution_price' => $subProduct->caution_price ?? 0,
                        'subscription_price' => $subProduct->subscription_price ?? 0
                    ];
                }
            }
        }

        return [
            'client_uuid' => $client->uuid,
            'parent_product' => [
                'id' => $parentProduct->id,
                'name' => $parentProduct->name,
                'property' => $parentProduct->property_name,
                'value' => $parentProduct->default_value
            ],
            'pricing' => [
                'total_caution_price' => $totalCautionPrice,
                'total_subscription_price' => $totalSubscriptionPrice,
                'currency' => 'EUR' // Assuming EUR, adjust as needed
            ],
            'matched_products' => $matchedProducts,
            'matched_products_count' => count($matchedProducts),
            'total_subproducts_analyzed' => $subProducts->count(),
            'calculation_timestamp' => now()->toDateTimeString()
        ];
    }

    /**
     * Check if client already exists by email
     * 
     * @param string $email
     * @return bool
     */
    public function clientExists(string $email): bool
    {
        return Client::where('email', $email)->exists();
    }

    /**
     * Get client by email
     * 
     * @param string $email
     * @return Client|null
     */
    public function getClientByEmail(string $email): ?Client
    {
        return Client::where('email', $email)->first();
    }
}
