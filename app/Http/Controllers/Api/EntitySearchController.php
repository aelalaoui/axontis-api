<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EntitySearchController extends Controller
{
    /**
     * Search entities by type and name
     */
    public function search(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'query' => 'nullable|string|max:255',
        ]);

        $entityType = $request->input('type');
        $searchQuery = $request->input('query', '');

        // Mapping des types d'entités aux tables et colonnes
        $entityMappings = [
            'App\\Models\\Client' => [
                'table' => 'clients',
                'id_column' => 'id',
                'name_column' => 'name',
                'additional_columns' => ['email', 'phone']
            ],
            'App\\Models\\Supplier' => [
                'table' => 'suppliers',
                'id_column' => 'id',
                'name_column' => 'name',
                'additional_columns' => ['email', 'contact_person']
            ],
            'App\\Models\\Order' => [
                'table' => 'orders',
                'id_column' => 'id',
                'name_column' => 'reference',
                'additional_columns' => ['status', 'total_amount']
            ],
            'App\\Models\\Device' => [
                'table' => 'devices',
                'id_column' => 'id',
                'name_column' => 'name',
                'additional_columns' => ['model', 'serial_number']
            ],
            'App\\Models\\Product' => [
                'table' => 'products',
                'id_column' => 'id',
                'name_column' => 'name',
                'additional_columns' => ['sku', 'brand']
            ],
            'App\\Models\\Contract' => [
                'table' => 'contracts',
                'id_column' => 'id',
                'name_column' => 'title',
                'additional_columns' => ['contract_number', 'status']
            ],
            'App\\Models\\User' => [
                'table' => 'users',
                'id_column' => 'id',
                'name_column' => 'name',
                'additional_columns' => ['email', 'role']
            ],
        ];

        if (!isset($entityMappings[$entityType])) {
            return response()->json([
                'error' => 'Type d\'entité non supporté'
            ], 400);
        }

        $mapping = $entityMappings[$entityType];

        // Vérifier si la table existe
        if (!DB::getSchemaBuilder()->hasTable($mapping['table'])) {
            return response()->json([
                'data' => []
            ]);
        }

        $queryBuilder = DB::table($mapping['table']);

        // Sélectionner les colonnes nécessaires
        $selectColumns = [
            $mapping['id_column'] . ' as id',
            $mapping['name_column'] . ' as name'
        ];

        // Ajouter les colonnes additionnelles si elles existent
        foreach ($mapping['additional_columns'] as $column) {
            if (DB::getSchemaBuilder()->hasColumn($mapping['table'], $column)) {
                $selectColumns[] = $column;
            }
        }

        $queryBuilder->select($selectColumns);

        // Appliquer la recherche si une requête est fournie
        if (!empty($searchQuery)) {
            $queryBuilder->where(function ($q) use ($mapping, $searchQuery) {
                $q->where($mapping['name_column'], 'like', "%{$searchQuery}%");

                // Rechercher aussi dans les colonnes additionnelles
                foreach ($mapping['additional_columns'] as $column) {
                    if (DB::getSchemaBuilder()->hasColumn($mapping['table'], $column)) {
                        $q->orWhere($column, 'like', "%{$searchQuery}%");
                    }
                }
            });
        }

        $results = $queryBuilder
            ->orderBy($mapping['name_column'])
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $results->map(function ($item) use ($mapping) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'display_name' => $this->formatDisplayName($item, $mapping),
                    'additional_info' => $this->getAdditionalInfo($item, $mapping)
                ];
            })
        ]);
    }

    /**
     * Format display name for the entity
     */
    private function formatDisplayName($item, $mapping)
    {
        $displayName = $item->name ?? 'Sans nom';

        // Ajouter des informations contextuelles selon le type
        if (isset($item->email) && !empty($item->email)) {
            $displayName .= " ({$item->email})";
        } elseif (isset($item->sku) && !empty($item->sku)) {
            $displayName .= " ({$item->sku})";
        } elseif (isset($item->contract_number) && !empty($item->contract_number)) {
            $displayName .= " ({$item->contract_number})";
        } elseif (isset($item->serial_number) && !empty($item->serial_number)) {
            $displayName .= " ({$item->serial_number})";
        }

        return $displayName;
    }

    /**
     * Get additional information for the entity
     */
    private function getAdditionalInfo($item, $mapping)
    {
        $info = [];

        foreach ($mapping['additional_columns'] as $column) {
            if (isset($item->$column) && !empty($item->$column)) {
                $info[$column] = $item->$column;
            }
        }

        return $info;
    }

    /**
     * Get available entity types
     */
    public function getEntityTypes()
    {
        $entityTypes = [
            [
                'value' => 'App\\Models\\Client',
                'label' => 'Client',
                'icon' => 'fas fa-user'
            ],
            [
                'value' => 'App\\Models\\Supplier',
                'label' => 'Fournisseur',
                'icon' => 'fas fa-truck'
            ],
            [
                'value' => 'App\\Models\\Order',
                'label' => 'Commande',
                'icon' => 'fas fa-shopping-cart'
            ],
            [
                'value' => 'App\\Models\\Device',
                'label' => 'Appareil',
                'icon' => 'fas fa-microchip'
            ],
            [
                'value' => 'App\\Models\\Product',
                'label' => 'Produit',
                'icon' => 'fas fa-box'
            ],
            [
                'value' => 'App\\Models\\Contract',
                'label' => 'Contrat',
                'icon' => 'fas fa-file-contract'
            ],
            [
                'value' => 'App\\Models\\User',
                'label' => 'Utilisateur',
                'icon' => 'fas fa-user-circle'
            ],
        ];

        return response()->json([
            'data' => $entityTypes
        ]);
    }
}
