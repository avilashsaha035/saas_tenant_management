<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionLimitExceededException extends Exception
{
    protected string $feature;
    protected int $currentUsage;
    protected int $allowedLimit;

    public function __construct(
        string $feature,
        int $currentUsage,
        int $allowedLimit,
        string $message = ''
    ) {
        $this->feature = $feature;
        $this->currentUsage = $currentUsage;
        $this->allowedLimit = $allowedLimit;

        if (empty($message)) {
            $message = "Subscription limit exceeded for feature '{$feature}'. Current usage: {$currentUsage}, Allowed limit: {$allowedLimit}. Please upgrade your subscription plan to add more.";
        }

        parent::__construct($message, Response::HTTP_FORBIDDEN);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'errors'  => [
                'feature'       => $this->feature,
                'current_usage' => $this->currentUsage,
                'allowed_limit' => $this->allowedLimit,
                'action_required' => 'upgrade_plan',
            ],
        ], Response::HTTP_FORBIDDEN);
    }
}
