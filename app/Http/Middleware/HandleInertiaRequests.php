<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            // This makes the logged-in user available to every single Vue page and layout automatically
            'auth' => [
                'user' => $request->user(),
            ],
            'semester_info' => [
                'name' => SystemSetting::get('semester'),
                'start_date' => $startDate = SystemSetting::get('semester_start_date'),
                'total_weeks' => (int) ($totalWeeks = SystemSetting::get('semester_total_weeks', 14)),
                'current_week' => (function () use ($startDate, $totalWeeks) {
                    if (! $startDate) {
                        return null;
                    }
                    $start = Carbon::parse($startDate)->startOfDay();
                    $now = Carbon::now()->startOfDay();

                    if ($now->lt($start)) {
                        return 1;
                    }

                    // Use start of week for consistency with timetable
                    $startOfCurrentWeek = $now->copy()->startOfWeek(Carbon::MONDAY);
                    $week = (int) $start->diffInWeeks($startOfCurrentWeek) + 1;

                    return min($week, (int) $totalWeeks);
                })(),
            ],
            'notifications' => $request->user() ? $request->user()->appNotifications()->latest()->take(10)->get() : [],
            'unread_count' => $request->user() ? $request->user()->appNotifications()->where('is_read', false)->count() : 0,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ]);
    }
}
