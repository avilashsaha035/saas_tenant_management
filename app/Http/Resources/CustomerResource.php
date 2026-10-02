<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'tenant_id'    => $this->tenant_id,
            'name'         => $this->name,
            'email'        => $this->email,
            'phone'        => $this->phone,
            'company_name' => $this->company_name,
            'status'       => $this->status,
            'revenue'      => (float) $this->revenue,
            'notes'        => $this->notes,
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }
}
