<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TenantSuspendedException extends Exception
{
    public function __construct(string $message = 'Tenant account is suspended or inactive. Please contact support.')
    {
        parent::__construct($message, Response::HTTP_FORBIDDEN);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors'  => [
                'tenant_status' => 'suspended',
            ],
        ], Response::HTTP_FORBIDDEN);
    }
}
