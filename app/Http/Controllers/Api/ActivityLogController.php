<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs for the authenticated user.
     */
    public function index(Request $request)
    {
        $query = ActivityLog::query()
            ->where('user_id', auth()->id()) // Only show user's own logs
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('resource_type')) {
            $query->where('resource_type', $request->resource_type);
        }

        if ($request->has('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = min($request->integer('per_page', 10), 50);
        $logs = $query->paginate($perPage);

        return ActivityLogResource::collection($logs);
    }

    /**
     * Display the specified activity log.
     */
    public function show(int $id)
    {
        $log = ActivityLog::findOrFail($id);

        // Authorization: User can only view their own logs
        if ($log->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized. You can only view your own activity logs.',
            ], Response::HTTP_FORBIDDEN);
        }

        return new ActivityLogResource($log);
    }
}
