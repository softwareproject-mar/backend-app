<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class MaximumIdLimitException extends Exception
{
    protected string $entityType;

    public function __construct(string $entityType)
    {
        $this->entityType = $entityType;
        parent::__construct("Maximum ID limit (99999) has been reached for entity '{$entityType}'");
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'MaximumIdLimitReached',
            'message' => $this->getMessage(),
            'entity_type' => $this->entityType,
            'current_count' => 99999,
            'suggestion' => 'Contact administrator to reset sequence or use different entity structure',
        ], 422);
    }
}
