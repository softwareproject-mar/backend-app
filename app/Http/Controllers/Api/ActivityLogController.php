<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Support\CaseInsensitiveSearch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs for the authenticated user.
     */
    public function index(Request $request)
    {
        $query = ActivityLog::query()->orderBy('created_at', 'desc');

        // Admin & Super Admin: semua aktivitas sistem. User biasa: hanya aktivitas sendiri
        if (! in_array(auth()->user()?->role ?? '', ['admin', 'super_admin'])) {
            $query->where('user_id', auth()->id());
        }

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
            CaseInsensitiveSearch::applyOrLikeContainsGroup($query, [
                'description',
                'user_name',
                'resource_type',
                'action_type',
                'status',
                'created_at',
            ], (string) $request->search);
        }

        // Pagination
        $perPage = min($request->integer('per_page', 100000), 999999);
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
