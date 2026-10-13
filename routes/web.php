<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OnboardingController;
use App\Http\Controllers\Auth\PasswordLoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\InstallmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductExpenseController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\Settings\AuditLogController;
use App\Http\Controllers\Settings\ExportController;
use App\Http\Controllers\Settings\SettingController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::view('/privacidade', 'legal.privacy')->name('privacy');
Route::view('/termos', 'legal.terms')->name('terms');
Route::get('/recibo/{sale}', [ReceiptController::class, 'public'])->middleware(['signed', 'throttle:30,1'])->whereNumber('sale')->name('receipts.public');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [PasswordLoginController::class, 'store'])->name('login.store');
    Route::get('/criar-conta', [RegisterController::class, 'create'])->name('register');
    Route::post('/criar-conta', [RegisterController::class, 'store'])->middleware('throttle:6,1')->name('register.store');
    Route::get('/esqueci-minha-senha', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/esqueci-minha-senha', [PasswordResetController::class, 'email'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/redefinir-senha/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/redefinir-senha', [PasswordResetController::class, 'update'])->middleware('throttle:6,1')->name('password.update');
    Route::get('/auth/google', [LoginController::class, 'redirect'])->middleware('throttle:20,1')->name('auth.google');
    Route::get('/auth/google/callback', [LoginController::class, 'callback'])->middleware('throttle:20,1')->name('auth.google.callback');
    Route::get('/comecar', [OnboardingController::class, 'create'])->name('onboarding.create');
    Route::post('/comecar', [OnboardingController::class, 'store'])->middleware('throttle:10,1')->name('onboarding.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'company', 'alerts'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/fotos/{path}', PhotoController::class)->where('path', '.*')->name('photos.show');

    Route::resource('customers', CustomerController::class)->except('destroy');
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'edit', 'update']);

    Route::get('/products/importar', [ProductImportController::class, 'create'])->name('products.import.create');
    Route::get('/products/importar/modelo', [ProductImportController::class, 'template'])->name('products.import.template');
    Route::post('/products/importar', [ProductImportController::class, 'store'])->middleware('throttle:10,1')->name('products.import.store');
    Route::resource('products', ProductController::class)->except('destroy');
    Route::post('/products/{product}/expenses', [ProductExpenseController::class, 'store'])->name('products.expenses.store');
    Route::get('/estoque', StockController::class)->name('stock.index');

    Route::resource('sales', SaleController::class)->except('destroy');
    Route::get('/sales/{sale}/recibo', [ReceiptController::class, 'show'])->name('receipts.show');
    Route::get('/sales/{sale}/recibo/whatsapp', [ReceiptController::class, 'whatsapp'])->name('receipts.whatsapp');

    Route::post('/installments/{installment}/pay', [InstallmentController::class, 'pay'])->name('installments.pay');
    Route::patch('/installments/{installment}/due-date', [InstallmentController::class, 'updateDueDate'])->name('installments.due-date');
    Route::get('/installments/{installment}/whatsapp', [WhatsAppController::class, 'installment'])->name('installments.whatsapp');

    Route::get('/notificacoes', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notificacoes/{notification}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notificacoes/ler-todas', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notificacoes/verificar', [NotificationController::class, 'refresh'])->name('notifications.refresh');

    Route::middleware('platform')->group(function () {
        Route::get('/plataforma', [PlatformController::class, 'index'])->name('platform.index');
        Route::patch('/plataforma/empresas/{company}', [PlatformController::class, 'toggle'])->name('platform.toggle');
    });

    Route::middleware('role:admin')->group(function () {
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::delete('/products/{product}/expenses/{expense}', [ProductExpenseController::class, 'destroy'])->name('products.expenses.destroy');
        Route::post('/sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');
        Route::delete('/sales/{sale}', [SaleController::class, 'destroy'])->name('sales.destroy');

        Route::get('/vendedores', SellerController::class)->name('sellers.index');
        Route::get('/financeiro', [FinanceController::class, 'index'])->name('finance.index');
        Route::post('/financeiro/lancamentos', [FinanceController::class, 'store'])->name('finance.store');
        Route::delete('/financeiro/lancamentos/{transaction}', [FinanceController::class, 'destroy'])->name('finance.destroy');

        Route::get('/relatorios', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/relatorios/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');

        Route::get('/configuracoes', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/configuracoes', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/configuracoes/resumo-semanal/previa', [SettingController::class, 'previewSummary'])->middleware('throttle:5,10')->name('settings.summary.preview');
        Route::get('/equipe', [UserController::class, 'index'])->name('users.index');
        Route::post('/equipe/convites', [UserController::class, 'invite'])->name('users.invite');
        Route::delete('/equipe/convites/{invitation}', [UserController::class, 'cancelInvite'])->name('users.invite.cancel');
        Route::patch('/equipe/{user}', [UserController::class, 'update'])->name('users.update');
        Route::get('/auditoria', [AuditLogController::class, 'index'])->name('audit.index');
        Route::get('/exportar', [ExportController::class, 'index'])->name('export.index');
        Route::get('/exportar/tudo', [ExportController::class, 'all'])->middleware('throttle:10,1')->name('export.all');
        Route::get('/exportar/{dataset}', [ExportController::class, 'one'])->middleware('throttle:30,1')->name('export.one');
    });
});
