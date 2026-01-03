<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityTransformer extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => [
                'ar' => $this->name_ar,
                'en' => $this->name_en,
                'fr' => $this->name_fr,
            ],
            'region' => new RegionTransformer($this->whenLoaded('region')),
        ];
    }
}

