<?php

namespace App\Traits;

use App\Models\ActivityLog;

trait LogsActivity
{
    /**
     * Perform an operation with automatic activity logging.
     *
     * @param string $action The action type: 'create', 'update', or 'delete'
     * @param callable $operation The actual CRUD operation to perform
     * @param array $context Context data for logging:
     *   - resource_type (required): Type of resource (e.g., 'anggota', 'data_kunjungan')
     *   - resource_id (optional): ID of the resource
     *   - description (required): Human-readable description
     *   - old_data (optional): Data before the operation (for update/delete)
     *   - new_data (optional): Data after the operation (for create/update)
     * @return mixed The result of the operation
     * @throws \Exception Re-throws any exception from the operation
     */
    protected function performWithLog(string $action, callable $operation, array $context)
    {
        try {
            // Execute the operation
            $result = $operation();
            
            // Log success
            $this->createActivityLog($action, $context, 'success');
            
            return $result;
        } catch (\Exception $e) {
            // Log failure
            $this->createActivityLog($action, $context, 'failed', $e->getMessage());
            
            // Re-throw exception (don't swallow errors)
            throw $e;
        }
    }

    /**
     * Create an activity log entry.
     *
     * @param string $action
     * @param array $context
     * @param string $status
     * @param string|null $errorMessage
     * @return void
     */
    private function createActivityLog(string $action, array $context, string $status, ?string $errorMessage = null): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'resource_type' => $context['resource_type'],
            'resource_id' => $context['resource_id'] ?? null,
            'action_type' => $action,
            'description' => $context['description'],
            'status' => $status,
            'error_message' => $errorMessage,
            'old_data' => $context['old_data'] ?? null,
            'new_data' => $context['new_data'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
