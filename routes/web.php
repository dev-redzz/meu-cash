<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\InstallmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductExpenseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\Settings\AuditLogController;
use App\Http\Controllers\Settings\BackupController;
use App\Http\Controllers\Settings\SettingController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware(['auth', 'active', 'alerts'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('customers', CustomerController::class)->except('destroy')->parameters(['customers' => 'customer']);
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'edit', 'update']);

    Route::resource('products', ProductController::class)->except('destroy');
    Route::post('/products/{product}/expenses', [ProductExpenseController::class, 'store'])->name('products.expenses.store');
    Route::get('/estoque', StockController::class)->name('stock.index');

    Route::resource('sales', SaleController::class)->except('destroy');

    Route::post('/installments/{installment}/pay', [InstallmentController::class, 'pay'])->name('installments.pay');
    Route::patch('/installments/{installment}/due-date', [InstallmentController::class, 'updateDueDate'])->name('installments.due-date');
    Route::get('/installments/{installment}/whatsapp', [WhatsAppController::class, 'installment'])->name('installments.whatsapp');

    Route::resource('services', ServiceController::class)->except('destroy');
    Route::post('/services/{service}/expenses', [ServiceController::class, 'storeExpense'])->name('services.expenses.store');

    Route::get('/notificacoes', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notificacoes/{notification}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notificacoes/ler-todas', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notificacoes/verificar', [NotificationController::class, 'refresh'])->name('notifications.refresh');

    Route::middleware('role:admin')->group(function () {
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::delete('/products/{product}/expenses/{expense}', [ProductExpenseController::class, 'destroy'])->name('products.expenses.destroy');
        Route::post('/sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');
        Route::post('/services/{service}/cancel', [ServiceController::class, 'cancel'])->name('services.cancel');
        Route::delete('/services/{service}/expenses/{expense}', [ServiceController::class, 'destroyExpense'])->name('services.expenses.destroy');

        Route::get('/financeiro', [FinanceController::class, 'index'])->name('finance.index');
        Route::post('/financeiro/lancamentos', [FinanceController::class, 'store'])->name('finance.store');
        Route::delete('/financeiro/lancamentos/{transaction}', [FinanceController::class, 'destroy'])->name('finance.destroy');

        Route::get('/relatorios', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/relatorios/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');

        Route::get('/configuracoes', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/configuracoes', [SettingController::class, 'update'])->name('settings.update');
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::get('/auditoria', [AuditLogController::class, 'index'])->name('audit.index');
        Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
        Route::post('/backup', [BackupController::class, 'store'])->name('backup.store');
        Route::post('/backup/restaurar', [BackupController::class, 'restore'])->name('backup.restore');
        Route::get('/backup/{file}', [BackupController::class, 'download'])->name('backup.download');
        Route::delete('/backup/{file}', [BackupController::class, 'destroy'])->name('backup.destroy');
    });
});
