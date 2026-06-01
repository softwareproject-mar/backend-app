<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics.
     */
    public function stats(): JsonResponse
    {
        $totalUsers = User::count();
        $totalAdmins = User::where('role', 'admin')->count();
        $totalActive = User::where('is_active', true)->count();
        $totalInactive = User::where('is_active', false)->count();

        return response()->json([
            'data' => [
                'total_users' => $totalUsers,
                'total_admins' => $totalAdmins,
                'total_active' => $totalActive,
                'total_inactive' => $totalInactive,
            ],
        ]);
    }

    /**
     * Get recent activities.
     */
    public function recentActivities(): JsonResponse
    {
        $logs = ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5000)
            ->get();

        return response()->json([
            'data' => ActivityLogResource::collection($logs),
        ]);
    }

    /**
     * Get chart data (registrations and activities per day/week).
     * Firebird tidak mengenal DATE() — pakai CAST(x AS DATE).
     */
    public function chartData(Request $request): JsonResponse
    {
        $period = $request->get('period', 'week');
        $days = $period === 'week' ? 7 : 30;

        $startDate = now()->subDays($days);

        $registrations = $this->countByDate(
            User::query()->where('created_at', '>=', $startDate)
        );

        $activities = $this->countByDate(
            ActivityLog::query()->where('created_at', '>=', $startDate)
        );

        $labels = [];
        $registrationData = [];
        $activityData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = $date;
            $registrationData[] = $registrations[$date] ?? 0;
            $activityData[] = $activities[$date] ?? 0;
        }

        return response()->json([
            'data' => [
                'labels' => $labels,
                'registrations' => $registrationData,
                'activities' => $activityData,
            ],
        ]);
    }

    /**
     * Group a query by date and return [date => count] array.
     * CAST(x AS DATE) works on both MySQL and Firebird; DATE() is MySQL-only.
     */
    private function countByDate(\Illuminate\Database\Eloquent\Builder $query): array
    {
        $rows = $query
            ->selectRaw('CAST("CREATED_AT" AS DATE) as grp_date, COUNT(*) as grp_count')
            ->groupByRaw('CAST("CREATED_AT" AS DATE)')
            ->orderByRaw('CAST("CREATED_AT" AS DATE)')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            $date = $row['grp_date'] ?? $row['GRP_DATE'] ?? null;
            $count = $row['grp_count'] ?? $row['GRP_COUNT'] ?? 0;
            if ($date !== null) {
                $result[(string) $date] = (int) $count;
            }
        }

        return $result;
    }
}
