<?php

use App\Http\Controllers\AccountingEntryController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\KardexController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\QuoteRequestController;
use App\Http\Controllers\RequirementController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransferOrderController;
use App\Http\Controllers\WarehouseReceptionController;
use App\Http\Controllers\DeliveryNoteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return auth()->check() ? redirect('/dashboard') : redirect('/login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    // Perfil (Breeze)
    Route::get('/profile',    [ProfileController::class, 'edit'])  ->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // MÓDULO 3: Requerimientos
    Route::resource('requirements', RequirementController::class);
    Route::patch('requirements/{requirement}/send-to-compras',
        [RequirementController::class, 'sendToCompras']
    )->name('requirements.send-to-compras');

    // MÓDULO 4: Proveedores
    Route::resource('suppliers', SupplierController::class);
    Route::patch('suppliers/{supplier}/toggle-status',
        [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle-status');

    // MÓDULO 5: Cotizaciones
    Route::resource('quote-requests', QuoteRequestController::class)
        ->except(['edit', 'update', 'destroy']);
    Route::prefix('quote-requests/{quoteRequest}')->group(function () {
        Route::patch('close',         [QuoteRequestController::class, 'close'])
            ->name('quote-requests.close');
        Route::get('quotes/create',   [QuoteController::class, 'create'])
            ->name('quote-requests.quotes.create');
        Route::post('quotes',         [QuoteController::class, 'store'])
            ->name('quote-requests.quotes.store');
        Route::post('select-winner',  [QuoteController::class, 'selectWinner'])
            ->name('quote-requests.select-winner');
    });

    // MÓDULO 6: Aprobaciones
    Route::resource('approvals', ApprovalController::class)->only(['index', 'show']);
    Route::post('approvals/{approval}/decide',
        [ApprovalController::class, 'decide'])->name('approvals.decide');

    // MÓDULO 6: Órdenes de Compra
    Route::resource('purchase-orders', PurchaseOrderController::class)->only(['index', 'show']);
    Route::patch('purchase-orders/{purchaseOrder}/send',
        [PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
    Route::patch('purchase-orders/{purchaseOrder}/cancel',
        [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');

    // MÓDULO 7: Facturas
    Route::resource('invoices', InvoiceController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('invoices/{invoice}/start-review',
        [InvoiceController::class, 'startReview'])->name('invoices.start-review');
    Route::post('invoices/{invoice}/observe',
        [InvoiceController::class, 'observe'])->name('invoices.observe');
    Route::patch('invoices/{invoice}/validate',
        [InvoiceController::class, 'validate'])->name('invoices.validate');

    // MÓDULO 7: Asientos Contables
    Route::get('invoices/{invoice}/accounting-entry/create',
        [AccountingEntryController::class, 'create'])->name('accounting-entries.create');
    Route::post('invoices/{invoice}/accounting-entry',
        [AccountingEntryController::class, 'store'])->name('accounting-entries.store');

    // MÓDULO 8: Pagos
    Route::get('payments',                           [PaymentController::class, 'index']) ->name('payments.index');
    Route::get('payment-obligations/{obligation}',   [PaymentController::class, 'show'])  ->name('payments.show');
    Route::get('payment-obligations/{obligation}/pay',[PaymentController::class, 'create'])->name('payments.create');
    Route::post('payment-obligations/{obligation}/pay',[PaymentController::class, 'store'])->name('payments.store');
    Route::patch('payment-obligations/{obligation}/confirm',
        [PaymentController::class, 'confirm'])->name('payments.confirm');
    Route::post('payments/{payment}/upload-voucher',
        [PaymentController::class, 'uploadVoucher'])->name('payments.upload-voucher');

    // MÓDULO 9: Recepciones, Stock y Kardex
    Route::resource('warehouse-receptions', WarehouseReceptionController::class)
        ->only(['index', 'create', 'store', 'show']);
    Route::get('stock',  [StockController::class,  'index'])->name('stock.index');
    Route::get('kardex', [KardexController::class, 'index'])->name('kardex.index');

    // MÓDULO 10: Traslados
    Route::resource('transfers', TransferOrderController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('transfers/{transfer}/dispatch',
        [TransferOrderController::class, 'dispatch'])->name('transfers.dispatch');
    Route::post('transfers/{transfer}/receive',
        [TransferOrderController::class, 'receive'])->name('transfers.receive');

    // MÓDULO 11: Entregas al Personal
    Route::resource('deliveries', DeliveryNoteController::class)
        ->only(['index', 'create', 'store', 'show']);
    Route::patch('deliveries/{delivery}/deliver',
        [DeliveryNoteController::class, 'deliver'])->name('deliveries.deliver');

    // MÓDULO 12: Usuarios y Roles (solo Administrador)
    Route::middleware('role:Administrador')->group(function () {
        Route::resource('users', UserController::class)
            ->except(['show']);
        Route::get('roles', [RoleController::class, 'index'])
            ->name('roles.index');
    });
    
    // Dentro del grupo middleware('auth'):
    Route::resource('products',   ProductController::class)->except(['show']);
    Route::resource('warehouses', WarehouseController::class)->except(['show']);
});

require __DIR__.'/auth.php';