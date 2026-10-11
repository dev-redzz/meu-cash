<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlatformController extends Controller
{
    public function index(Request $request): View
    {
        $monthStart = Carbon::today()->startOfMonth()->toDateString();

        $companies = Company::query()
            ->when($request->input('q'), fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$term}%")
                ->orWhere('document', 'like', "%{$term}%")
                ->orWhereIn('id', DB::table('users')->where('email', 'like', "%{$term}%")->select('company_id'))))
            ->when($request->input('situacao') === 'ativas', fn ($q) => $q->where('active', true))
            ->when($request->input('situacao') === 'suspensas', fn ($q) => $q->where('active', false))
            ->addSelect([
                'users_count' => DB::table('users')->selectRaw('COUNT(*)')->whereColumn('users.company_id', 'companies.id'),
                'owner_email' => DB::table('users')->select('email')->whereColumn('users.company_id', 'companies.id')->where('role', 'admin')->orderBy('id')->limit(1),
                'products_count' => DB::table('products')->selectRaw('COUNT(*)')->whereColumn('products.company_id', 'companies.id'),
                'sales_count' => DB::table('sales')->selectRaw('COUNT(*)')->whereColumn('sales.company_id', 'companies.id')->where('status', 'concluida'),
                'month_revenue' => DB::table('sales')->selectRaw('COALESCE(SUM(total), 0)')->whereColumn('sales.company_id', 'companies.id')->where('status', 'concluida')->where('sale_date', '>=', $monthStart),
                'last_activity' => DB::table('audit_logs')->selectRaw('MAX(created_at)')->whereColumn('audit_logs.company_id', 'companies.id'),
            ])
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $since = Carbon::now()->subDays(7);

        return view('platform.index', [
            'companies' => $companies,
            'stats' => [
                'companies' => Company::count(),
                'active' => Company::where('active', true)->count(),
                'new_month' => Company::where('created_at', '>=', $monthStart)->count(),
                'users' => DB::table('users')->count(),
                'active_week' => DB::table('audit_logs')->where('created_at', '>=', $since)->distinct()->count('company_id'),
                'sales_month' => (int) DB::table('sales')->where('status', 'concluida')->where('sale_date', '>=', $monthStart)->count(),
            ],
        ]);
    }

    public function toggle(Request $request, Company $company): RedirectResponse
    {
        if ($company->id === $request->user()->company_id) {
            return back()->with('error', 'Você não pode suspender a própria empresa.');
        }

        $company->update(['active' => ! $company->active]);
        AuditService::log('configuracoes', ($company->active ? 'Reativou' : 'Suspendeu')." a empresa {$company->name} (#{$company->id}) pela plataforma");

        return back()->with('success', "Empresa {$company->name} ".($company->active ? 'reativada.' : 'suspensa.'));
    }
}
