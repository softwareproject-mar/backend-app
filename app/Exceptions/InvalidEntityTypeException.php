<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidEntityTypeException extends Exception
{
    protected string $entityType;

    public function __construct(string $entityType)
    {
        $this->entityType = $entityType;
        parent::__construct("Invalid entity type: '{$entityType}'");
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        $supportedTypes = array_keys(config('id_generator.entity_mappings', []));

        return response()->json([
            'error' => 'InvalidEntityType',
            'message' => $this->getMessage(),
            'entity_type' => $this->entityType,
            'supported_types' => $supportedTypes,
        ], 400);
    }
}
