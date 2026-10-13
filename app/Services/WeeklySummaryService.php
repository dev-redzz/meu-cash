<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Mail\WeeklySummaryMail;
use App\Models\Installment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\User;
use App\Support\Period;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WeeklySummaryService
{
    public function __construct(
        private readonly FinanceService $finance,
        private readonly SellerPerformanceService $sellers,
    ) {
    }

    public function build(?Carbon $reference = null): array
    {
        $reference ??= Carbon::today();
        $start = $reference->copy()->subWeek()->startOfWeek(Carbon::MONDAY);
        $end = $start->copy()->endOfWeek(Carbon::SUNDAY);
        $week = new Period($start, $end, 'personalizado');
        $previous = new Period($start->copy()->subWeek(), $end->copy()->subWeek(), 'personalizado');

        $current = $this->finance->summary($week);
        $before = $this->finance->summary($previous);

        $overdue = Installment::open()
            ->whereDate('due_date', '<', $reference->toDateString())
            ->with('customer')
            ->orderBy('due_date');

        $upcomingEnd = $reference->copy()->addDays(6);
        $upcoming = Installment::open()
            ->whereBetween('due_date', [$reference->toDateString(), $upcomingEnd->toDateString()]);

        $topProducts = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'concluida')
            ->whereBetween('sales.sale_date', [$week->startDate(), $week->endDate()])
            ->selectRaw('sale_items.description, SUM(sale_items.quantity) as quantity, SUM(sale_items.total) as total')
            ->groupBy('sale_items.description')
            ->orderByDesc('quantity')
            ->limit(5)
            ->get();

        $sellers = $this->sellers->forPeriod($week)->filter(fn ($row) => $row->sales > 0)->values();

        return [
            'company' => tenant()->company()->name,
            'start' => $start,
            'end' => $end,
            'revenue' => $current['revenue'],
            'revenue_change' => $this->finance->compare($current['revenue'], $before['revenue']),
            'profit' => $current['profit'],
            'profit_change' => $this->finance->compare($current['profit'], $before['profit']),
            'sales_count' => $current['sales_count'],
            'sales_count_before' => $before['sales_count'],
            'received' => $current['received'],
            'spent' => $current['spent'],
            'balance' => $current['balance'],
            'overdue_count' => (clone $overdue)->count(),
            'overdue_total' => round((float) (clone $overdue)->sum('amount'), 2),
            'overdue_list' => (clone $overdue)->limit(5)->get(),
            'upcoming_count' => (clone $upcoming)->count(),
            'upcoming_total' => round((float) (clone $upcoming)->sum('amount'), 2),
            'upcoming_end' => $upcomingEnd,
            'top_products' => $topProducts,
            'best_seller' => $sellers->count() > 1 ? $sellers->first() : null,
            'company_goal' => $this->sellers->companyGoal(),
        ];
    }

    public static function weekKey(?Carbon $reference = null): string
    {
        return ($reference ?? Carbon::today())->copy()->subWeek()->startOfWeek(Carbon::MONDAY)->format('o-\\WW');
    }

    public function isDue(): bool
    {
        return Setting::get('weekly_summary', '1') === '1'
            && Setting::get('weekly_summary_last') !== self::weekKey();
    }

    /** Envia o resumo da semana anterior para os administradores da empresa atual (uma vez por semana). */
    public function sendForCurrentCompany(): int
    {
        if (! $this->isDue()) {
            return 0;
        }

        $lock = Cache::lock('weekly-summary:'.tenant_id().':'.self::weekKey(), 300);
        if (! $lock->get()) {
            return 0;
        }

        try {
            if (! $this->isDue()) {
                return 0;
            }

            Setting::put('weekly_summary_last', self::weekKey());

            $summary = $this->build();
            if (! $this->hasActivity($summary)) {
                return 0;
            }

            $sent = 0;
            User::where('company_id', tenant_id())
                ->where('role', UserRole::Admin->value)
                ->where('active', true)
                ->get()
                ->each(function (User $user) use ($summary, &$sent) {
                    try {
                        Mail::to($user->email, $user->name)->send(new WeeklySummaryMail($summary, $user));
                        $sent++;
                    } catch (\Throwable $e) {
                        Log::warning("Resumo semanal não enviado para {$user->email}: ".$e->getMessage());
                    }
                });

            return $sent;
        } finally {
            $lock->release();
        }
    }

    public function hasActivity(array $summary): bool
    {
        return $summary['sales_count'] > 0
            || $summary['sales_count_before'] > 0
            || $summary['received'] > 0
            || $summary['spent'] > 0
            || $summary['overdue_count'] > 0
            || $summary['upcoming_count'] > 0;
    }
}
