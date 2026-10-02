<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'slug'          => $this->slug,
            'description'   => $this->description,
            'price'         => (float) $this->price,
            'currency'      => $this->currency,
            'billing_cycle' => $this->billing_cycle,
            'is_active'     => (bool) $this->is_active,
            'features'      => $this->whenLoaded('features', function () {
                return [
                    'max_users'              => $this->features->max_users,
                    'max_customers'          => $this->features->max_customers,
                    'has_advanced_analytics' => (bool) $this->features->has_advanced_analytics,
                    'has_api_access'         => (bool) $this->features->has_api_access,
                    'rate_limit_per_minute'  => $this->features->rate_limit_per_minute,
                    'custom_features'        => $this->features->custom_features,
                ];
            }),
        ];
    }
}
