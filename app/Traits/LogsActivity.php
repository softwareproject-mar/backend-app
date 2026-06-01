<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    /**
     * Muat ulang model dari DB setelah update. Di Firebird, Model::fresh()
     * kadang mengembalikan null meskipun baris ada; findOrFail konsisten.
     */
    protected function reloadModelAfterUpdate(Model $model): Model
    {
        $class = $model::class;

        return $class::query()->findOrFail($model->getKey());
    }

    /**
     * Perform an operation with automatic activity logging.
     *
     * @param  string  $action  The action type: 'create', 'update', or 'delete'
     * @param  callable  $operation  The actual CRUD operation to perform
     * @param  array  $context  Context data for logging:
     *                          - resource_type (required): Type of resource (e.g., 'anggota', 'data_kunjungan')
     *                          - resource_id (optional): ID of the resource
     *                          - description (required): Human-readable description
     *                          - old_data (optional): Data before the operation (for update/delete)
     *                          - new_data (optional): Data after the operation (for create/update)
     * @return mixed The result of the operation
     *
     * @throws \Exception Re-throws any exception from the operation
     */
    protected function performWithLog(string $action, callable $operation, array $context)
    {
        try {
            // Execute the operation
            $result = $operation();

            if ($action === 'create' && $result instanceof Model) {
                $context['resource_id'] = (string) $result->getKey();
            }

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
     */
    private function createActivityLog(string $action, array $context, string $status, ?string $errorMessage = null): void
    {
        $user = auth()->user();
        $userId = auth()->id();

        // Skip logging if no authenticated user (e.g., CLI commands)
        if (! $userId) {
            return;
        }

        ActivityLog::create([
            'user_id' => $userId,
            'user_name' => $user ? $user->name : 'System',
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
