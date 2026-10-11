<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use App\Support\Period;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SellerPerformanceService
{
    public function forPeriod(Period $period): Collection
    {
        $stats = Sale::completed()
            ->whereBetween('sale_date', [$period->startDate(), $period->endDate()])
            ->selectRaw('user_id, COUNT(*) as sales, SUM(total) as revenue, SUM(profit) as profit, SUM(commission) as commission')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $month = $this->monthRevenueByUser();

        return User::where('company_id', tenant_id())
            ->orderBy('name')
            ->get()
            ->map(function (User $user) use ($stats, $month) {
                $row = $stats->get($user->id);
                $goal = (float) $user->monthly_goal;
                $monthRevenue = (float) ($month[$user->id] ?? 0);

                return (object) [
                    'user' => $user,
                    'sales' => (int) ($row->sales ?? 0),
                    'revenue' => round((float) ($row->revenue ?? 0), 2),
                    'profit' => round((float) ($row->profit ?? 0), 2),
                    'commission' => round((float) ($row->commission ?? 0), 2),
                    'goal' => $goal,
                    'month_revenue' => $monthRevenue,
                    'progress' => $goal > 0 ? round($monthRevenue / $goal * 100, 1) : null,
                ];
            })
            ->filter(fn ($row) => $row->user->active || $row->sales > 0)
            ->sortByDesc('revenue')
            ->values();
    }

    public function companyGoal(): ?object
    {
        $goal = (float) Setting::get('monthly_goal', 0);

        if ($goal <= 0) {
            return null;
        }

        return $this->goal($goal, (float) array_sum($this->monthRevenueByUser()));
    }

    public function userGoal(User $user): ?object
    {
        $goal = (float) $user->monthly_goal;

        if ($goal <= 0) {
            return null;
        }

        return $this->goal($goal, (float) ($this->monthRevenueByUser()[$user->id] ?? 0));
    }

    private function goal(float $goal, float $done): object
    {
        $today = Carbon::today();
        $daysLeft = (int) $today->diffInDays($today->copy()->endOfMonth()) + 1;
        $missing = max(0, $goal - $done);

        return (object) [
            'goal' => $goal,
            'done' => round($done, 2),
            'progress' => round($done / $goal * 100, 1),
            'missing' => round($missing, 2),
            'per_day' => $missing > 0 ? round($missing / $daysLeft, 2) : 0,
            'days_left' => $daysLeft,
        ];
    }

    private function monthRevenueByUser(): array
    {
        return Sale::completed()
            ->whereBetween('sale_date', [Carbon::today()->startOfMonth()->toDateString(), Carbon::today()->endOfMonth()->toDateString()])
            ->selectRaw('user_id, SUM(total) as revenue')
            ->groupBy('user_id')
            ->pluck('revenue', 'user_id')
            ->map(fn ($v) => (float) $v)
            ->all();
    }
}
