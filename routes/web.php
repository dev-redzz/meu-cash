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

Route::get('/', [\App\Http\Controllers\SiteController::class, 'home'])->name('home');
Route::view('/privacidade', 'legal.privacy')->name('privacy');
Route::view('/termos', 'legal.terms')->name('terms');
Route::prefix('cliente')->name('portal.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Portal\PortalController::class, 'login'])->name('login');
    Route::post('/', [\App\Http\Controllers\Portal\PortalController::class, 'authenticate'])->middleware('throttle:20,1')->name('authenticate');
    Route::middleware('portal')->group(function () {
        Route::get('/minha-area', [\App\Http\Controllers\Portal\PortalController::class, 'home'])->name('home');
        Route::get('/compras/{sale}/comprovante', [\App\Http\Controllers\Portal\PortalController::class, 'receipt'])->whereNumber('sale')->middleware('throttle:30,1')->name('receipt');
        Route::post('/sair', [\App\Http\Controllers\Portal\PortalController::class, 'logout'])->name('logout');
        Route::post('/parcelas/{installment}/pix', [\App\Http\Controllers\Portal\PixController::class, 'create'])->whereNumber('installment')->middleware('throttle:10,1')->name('pix.create');
        Route::get('/parcelas/{installment}/pix', [\App\Http\Controllers\Portal\PixController::class, 'show'])->whereNumber('installment')->name('pix.show');
        Route::get('/parcelas/{installment}/pix/situacao', [\App\Http\Controllers\Portal\PixController::class, 'status'])->whereNumber('installment')->middleware('throttle:40,1')->name('pix.status');
    });
});
Route::post('/webhooks/mercadopago/loja/{company}', [\App\Http\Controllers\MercadoPagoWebhookController::class, 'store'])->whereNumber('company')->middleware('throttle:120,1')->name('webhooks.mercadopago.store');
Route::post('/webhooks/mercadopago/plataforma', [\App\Http\Controllers\MercadoPagoWebhookController::class, 'platform'])->middleware('throttle:120,1')->name('webhooks.mercadopago.platform');
Route::get('/loja/{slug}', [\App\Http\Controllers\CatalogController::class, 'show'])->where('slug', '[a-z0-9-]+')->middleware('throttle:120,1')->name('catalog.show');
Route::get('/loja/{slug}/foto/{product}/{index}', [\App\Http\Controllers\CatalogController::class, 'photo'])->where('slug', '[a-z0-9-]+')->whereNumber(['product', 'index'])->middleware('throttle:600,1')->name('catalog.photo');
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

Route::middleware(['auth', 'company', 'alerts', 'plan'])->group(function () {
    Route::post('/desfazer/{token}', \App\Http\Controllers\UndoController::class)->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:20,1')->name('undo');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/fotos/{path}', PhotoController::class)->where('path', '.*')->name('photos.show');

    Route::resource('customers', CustomerController::class)->except('destroy');
    Route::get('/customers/{customer}/acesso/whatsapp', [\App\Http\Controllers\CustomerAccessController::class, 'whatsapp'])->name('customers.access.whatsapp');
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'edit', 'update']);

    Route::get('/products/importar', [ProductImportController::class, 'create'])->name('products.import.create');
    Route::get('/products/importar/modelo', [ProductImportController::class, 'template'])->name('products.import.template');
    Route::post('/products/importar', [ProductImportController::class, 'store'])->middleware('throttle:10,1')->name('products.import.store');
    Route::get('/etiquetas', [\App\Http\Controllers\LabelController::class, 'index'])->name('labels.index');
    Route::get('/etiquetas/imprimir', [\App\Http\Controllers\LabelController::class, 'print'])->name('labels.print');
    Route::resource('products', ProductController::class)->except('destroy');
    Route::post('/products/{product}/repor', [ProductController::class, 'restock'])->name('products.restock');
    Route::post('/products/{product}/expenses', [ProductExpenseController::class, 'store'])->name('products.expenses.store');
    Route::get('/estoque', StockController::class)->name('stock.index');

    Route::get('/buscar', \App\Http\Controllers\SearchController::class)->name('search');
    Route::resource('sales', SaleController::class)->except('destroy');
    Route::get('/sales/{sale}/recibo', [ReceiptController::class, 'show'])->name('receipts.show');
    Route::get('/sales/{sale}/troca', [\App\Http\Controllers\ExchangeController::class, 'create'])->name('sales.exchange');
    Route::post('/sales/{sale}/troca', [\App\Http\Controllers\ExchangeController::class, 'store'])->middleware('throttle:20,1')->name('sales.exchange.store');
    Route::post('/sales/{sale}/nota', [\App\Http\Controllers\InvoiceController::class, 'store'])->middleware('throttle:20,1')->name('invoices.store');
    Route::post('/notas/{invoice}/atualizar', [\App\Http\Controllers\InvoiceController::class, 'refresh'])->middleware('throttle:30,1')->name('invoices.refresh');
    Route::get('/notas/{invoice}/{kind}', [\App\Http\Controllers\InvoiceController::class, 'download'])->whereIn('kind', ['pdf', 'xml'])->name('invoices.download');
    Route::get('/sales/{sale}/recibo/whatsapp', [ReceiptController::class, 'whatsapp'])->name('receipts.whatsapp');

    Route::post('/installments/{installment}/pay', [InstallmentController::class, 'pay'])->name('installments.pay');
    Route::patch('/installments/{installment}/due-date', [InstallmentController::class, 'updateDueDate'])->name('installments.due-date');
    Route::get('/installments/{installment}/whatsapp', [WhatsAppController::class, 'installment'])->name('installments.whatsapp');

    Route::post('/sugestoes', [\App\Http\Controllers\FeedbackController::class, 'store'])->middleware('throttle:6,10')->name('feedback.store');
    Route::get('/notificacoes', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notificacoes/{notification}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notificacoes/ler-todas', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notificacoes/verificar', [NotificationController::class, 'refresh'])->name('notifications.refresh');

    Route::middleware('platform')->group(function () {
        Route::get('/plataforma', [PlatformController::class, 'index'])->name('platform.index');
        Route::patch('/plataforma/empresas/{company}', [PlatformController::class, 'toggle'])->name('platform.toggle');
        Route::post('/plataforma/backup', [PlatformController::class, 'backupNow'])->middleware('throttle:5,10')->name('platform.backup');
        Route::get('/plataforma/backup/baixar', [PlatformController::class, 'backupDownload'])->middleware('throttle:5,10')->name('platform.backup.download');
        Route::get('/plataforma/erros', [PlatformController::class, 'errors'])->name('platform.errors');
        Route::get('/plataforma/sugestoes', [PlatformController::class, 'feedback'])->name('platform.feedback');
        Route::post('/plataforma/sugestoes/lidas', [PlatformController::class, 'feedbackRead'])->name('platform.feedback.read');
        Route::post('/plataforma/empresas/{company}/cortesia', [PlatformController::class, 'courtesy'])->name('platform.courtesy');
        Route::patch('/plataforma/empresas/{company}/isencao', [PlatformController::class, 'exempt'])->name('platform.exempt');
        Route::patch('/plataforma/erros/{report}/resolver', [PlatformController::class, 'resolveError'])->name('platform.errors.resolve');
        Route::delete('/plataforma/erros/resolvidos', [PlatformController::class, 'clearResolved'])->name('platform.errors.clear');
    });

    Route::middleware('role:admin')->group(function () {
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
        Route::post('/customers/{customer}/codigo', [\App\Http\Controllers\CustomerAccessController::class, 'regenerate'])->name('customers.access.regenerate');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::delete('/products/{product}/expenses/{expense}', [ProductExpenseController::class, 'destroy'])->name('products.expenses.destroy');
        Route::post('/sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');
        Route::post('/notas/{invoice}/cancelar', [\App\Http\Controllers\InvoiceController::class, 'cancel'])->middleware('throttle:10,1')->name('invoices.cancel');
        Route::get('/configuracoes/catalogo', [\App\Http\Controllers\Settings\CatalogSettingController::class, 'edit'])->name('settings.catalog');
        Route::put('/configuracoes/catalogo', [\App\Http\Controllers\Settings\CatalogSettingController::class, 'update'])->name('settings.catalog.update');
        Route::get('/configuracoes/nota-fiscal', [\App\Http\Controllers\Settings\FiscalSettingController::class, 'edit'])->name('settings.fiscal');
        Route::put('/configuracoes/nota-fiscal', [\App\Http\Controllers\Settings\FiscalSettingController::class, 'update'])->middleware('throttle:20,1')->name('settings.fiscal.update');
        Route::post('/configuracoes/nota-fiscal/certificado', [\App\Http\Controllers\Settings\FiscalSettingController::class, 'certificate'])->middleware('throttle:10,1')->name('settings.fiscal.certificate');
        Route::delete('/sales/{sale}', [SaleController::class, 'destroy'])->name('sales.destroy');

        Route::get('/vendedores', SellerController::class)->name('sellers.index');
        Route::get('/financeiro', [FinanceController::class, 'index'])->name('finance.index');
        Route::post('/financeiro/lancamentos', [FinanceController::class, 'store'])->name('finance.store');
        Route::delete('/financeiro/lancamentos/{transaction}', [FinanceController::class, 'destroy'])->name('finance.destroy');

        Route::get('/relatorios', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/relatorios/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');

        Route::get('/assinatura', [\App\Http\Controllers\BillingController::class, 'show'])->name('billing.show');
        Route::post('/assinatura/pagar', [\App\Http\Controllers\BillingController::class, 'checkout'])->middleware('throttle:10,1')->name('billing.checkout');
        Route::get('/assinatura/retorno', [\App\Http\Controllers\BillingController::class, 'return'])->name('billing.return');
        Route::get('/configuracoes/recebimentos', [\App\Http\Controllers\Settings\PaymentSettingController::class, 'edit'])->name('settings.payments');
        Route::put('/configuracoes/recebimentos', [\App\Http\Controllers\Settings\PaymentSettingController::class, 'update'])->middleware('throttle:10,1')->name('settings.payments.update');
        Route::delete('/configuracoes/recebimentos', [\App\Http\Controllers\Settings\PaymentSettingController::class, 'destroy'])->name('settings.payments.destroy');
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
