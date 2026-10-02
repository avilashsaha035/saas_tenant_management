<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'plan_id'       => $this->plan_id,
            'plan'          => new PlanResource($this->whenLoaded('plan')),
            'status'        => $this->status,
            'is_active'     => $this->isActive(),
            'starts_at'     => $this->starts_at?->toIso8601String(),
            'ends_at'       => $this->ends_at?->toIso8601String(),
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'cancels_at'    => $this->cancels_at?->toIso8601String(),
        ];
    }
}
