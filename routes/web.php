<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GarmentTypeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ManufactureUnitController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OutletController;
use App\Http\Controllers\PaymentManagementController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RawMaterialPurchaseController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TaskManagementController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\WorkerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

require __DIR__.'/auth.php';

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('can:view-dashboard');
    Route::get('/search', [DashboardController::class, 'search'])->name('search.index');
    Route::get('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');

    Route::group(['prefix' => 'outlet', 'as' => 'outlet.'], function () {
        Route::get('/', [OutletController::class, 'index'])->name('index')->middleware('can:view-outlets,manage-outlets');
        Route::get('/create', [OutletController::class, 'create'])->name('create')->middleware('can:create-outlets,manage-outlets');
        Route::post('/', [OutletController::class, 'store'])->name('store')->middleware('can:create-outlets,manage-outlets');
        Route::get('/edit/{outlet}', [OutletController::class, 'edit'])->name('edit')->middleware('can:edit-outlets,manage-outlets');
        Route::put('/update/{outlet}', [OutletController::class, 'update'])->name('update')->middleware('can:edit-outlets,manage-outlets');
        Route::delete('/delete/{outlet}', [OutletController::class, 'destroy'])->name('destroy')->middleware('can:delete-outlets,manage-outlets');
        Route::post('/switch', [OutletController::class, 'switch'])->name('switch');
    });

    Route::group(['prefix' => 'role', 'as' => 'role.'], function () {
        Route::get('/', [RoleController::class, 'index'])->name('index')->middleware('can:view-roles,manage-roles');
        Route::get('/create', [RoleController::class, 'create'])->name('create')->middleware('can:create-roles,manage-roles');
        Route::post('/', [RoleController::class, 'store'])->name('store')->middleware('can:create-roles,manage-roles');
        Route::get('/edit/{role}', [RoleController::class, 'edit'])->name('edit')->middleware('can:edit-roles,manage-roles');
        Route::put('/update/{role}', [RoleController::class, 'update'])->name('update')->middleware('can:edit-roles,manage-roles');
        Route::put('/update-permissions/{role}', [RoleController::class, 'updatePermissions'])->name('updatePermissions')->middleware('can:edit-roles,manage-roles');
        Route::delete('/delete/{role}', [RoleController::class, 'destroy'])->name('destroy')->middleware('can:delete-roles,manage-roles');
    });

    Route::group(['prefix' => 'user', 'as' => 'user.'], function () {
        Route::get('/', [UserController::class, 'index'])->name('index')->middleware('can:view-users,manage-users');
        Route::get('/create', [UserController::class, 'create'])->name('create')->middleware('can:create-users,manage-users');
        Route::post('/', [UserController::class, 'store'])->name('store')->middleware('can:create-users,manage-users');
        Route::get('/edit/{user}', [UserController::class, 'edit'])->name('edit')->middleware('can:edit-users,manage-users');
        Route::put('/update/{user}', [UserController::class, 'update'])->name('update')->middleware('can:edit-users,manage-users');
        Route::put('/update-roles/{user}', [UserController::class, 'updateRoles'])->name('updateRoles')->middleware('can:edit-users,manage-users');
        Route::put('/update-permissions/{user}', [UserController::class, 'updatePermissions'])->name('updatePermissions')->middleware('can:edit-users,manage-users');
        Route::delete('/delete/{user}', [UserController::class, 'destroy'])->name('destroy')->middleware('can:delete-users,manage-users');
    });

    Route::group(['prefix' => 'worker', 'as' => 'worker.'], function () {
        Route::get('/', [WorkerController::class, 'index'])->name('index')->middleware('can:view-users,manage-users');
        Route::get('/create', [WorkerController::class, 'create'])->name('create')->middleware('can:create-users,manage-users');
        Route::post('/', [WorkerController::class, 'store'])->name('store')->middleware('can:create-users,manage-users');
        Route::get('/tasks/{worker}', [WorkerController::class, 'tasks'])->name('tasks')->middleware('can:view-task-management,manage-task-management,manage-orders');
        Route::get('/edit/{worker}', [WorkerController::class, 'edit'])->name('edit')->middleware('can:edit-users,manage-users');
        Route::put('/update/{worker}', [WorkerController::class, 'update'])->name('update')->middleware('can:edit-users,manage-users');
        Route::put('/update-permissions/{worker}', [WorkerController::class, 'updatePermissions'])->name('updatePermissions')->middleware('can:edit-users,manage-users');
        Route::delete('/delete/{worker}', [WorkerController::class, 'destroy'])->name('destroy')->middleware('can:delete-users,manage-users');
    });

    Route::group(['prefix' => 'unit', 'as' => 'unit.'], function () {
        Route::get('/', [UnitController::class, 'index'])->name('index')->middleware('can:view-units,manage-units');
        Route::get('/create', [UnitController::class, 'create'])->name('create')->middleware('can:create-units,manage-units');
        Route::post('/', [UnitController::class, 'store'])->name('store')->middleware('can:create-units,manage-units');
        Route::get('/edit/{unit}', [UnitController::class, 'edit'])->name('edit')->middleware('can:edit-units,manage-units');
        Route::put('/update/{unit}', [UnitController::class, 'update'])->name('update')->middleware('can:edit-units,manage-units');
        Route::delete('/delete/{unit}', [UnitController::class, 'destroy'])->name('destroy')->middleware('can:delete-units,manage-units');
    });

    Route::group(['prefix' => 'customer', 'as' => 'customer.'], function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index')->middleware('can:view-customers,manage-customers');
        Route::get('/create', [CustomerController::class, 'create'])->name('create')->middleware('can:create-customers,manage-customers');
        Route::post('/', [CustomerController::class, 'store'])->name('store')->middleware('can:create-customers,manage-customers');
        Route::get('/edit/{customer}', [CustomerController::class, 'edit'])->name('edit')->middleware('can:edit-customers,manage-customers');
        Route::get('/show/{customer}', [CustomerController::class, 'show'])->name('show')->middleware('can:view-customers,manage-customers');
        Route::put('/update/{customer}', [CustomerController::class, 'update'])->name('update')->middleware('can:edit-customers,manage-customers');
        Route::put('/update-measurements/{customer}', [CustomerController::class, 'updateMeasurements'])->name('updateMeasurements')->middleware('can:edit-customers,manage-customers');
        Route::delete('/delete/{customer}', [CustomerController::class, 'destroy'])->name('destroy')->middleware('can:delete-customers,manage-customers');
    });

    Route::group(['prefix' => 'product', 'as' => 'product.'], function () {
        Route::get('/', [ProductController::class, 'index'])->name('index')->middleware('can:view-products,manage-products');
        Route::get('/barcodes/pdf', [ProductController::class, 'barcodesPdf'])->name('barcodes.pdf')->middleware('can:view-products,manage-products');
        Route::get('/create', [ProductController::class, 'create'])->name('create')->middleware('can:create-products,manage-products');
        Route::post('/', [ProductController::class, 'store'])->name('store')->middleware('can:create-products,manage-products');
        Route::get('/edit/{product}', [ProductController::class, 'edit'])->name('edit')->middleware('can:edit-products,manage-products');
        Route::put('/update/{product}', [ProductController::class, 'update'])->name('update')->middleware('can:edit-products,manage-products');
        Route::post('/{product}/inventory', [ProductController::class, 'adjustInventory'])->name('inventory.store')->middleware('can:manage-inventory');
        Route::delete('/delete/{product}', [ProductController::class, 'destroy'])->name('destroy')->middleware('can:delete-products,manage-products');
    });

    Route::group(['prefix' => 'vendor-management', 'as' => 'vendor.'], function () {
        Route::get('/', [VendorController::class, 'index'])->name('index')->middleware('can:view-vendors,manage-vendors');
        Route::get('/create', [VendorController::class, 'create'])->name('create')->middleware('can:create-vendors,manage-vendors');
        Route::post('/', [VendorController::class, 'store'])->name('store')->middleware('can:create-vendors,manage-vendors');
        Route::get('/edit/{vendor}', [VendorController::class, 'edit'])->name('edit')->middleware('can:edit-vendors,manage-vendors');
        Route::put('/update/{vendor}', [VendorController::class, 'update'])->name('update')->middleware('can:edit-vendors,manage-vendors');
        Route::delete('/delete/{vendor}', [VendorController::class, 'destroy'])->name('destroy')->middleware('can:delete-vendors,manage-vendors');
    });

    Route::group(['prefix' => 'raw-material-purchase', 'as' => 'rawMaterialPurchase.'], function () {
        Route::get('/', [RawMaterialPurchaseController::class, 'index'])->name('index')->middleware('can:view-raw-material-purchases,manage-raw-material-purchases');
        Route::get('/create', [RawMaterialPurchaseController::class, 'create'])->name('create')->middleware('can:create-raw-material-purchases,manage-raw-material-purchases');
        Route::post('/', [RawMaterialPurchaseController::class, 'store'])->name('store')->middleware('can:create-raw-material-purchases,manage-raw-material-purchases');
        Route::get('/edit/{purchase}', [RawMaterialPurchaseController::class, 'edit'])->name('edit')->middleware('can:manage-raw-material-purchases');
        Route::put('/update/{purchase}', [RawMaterialPurchaseController::class, 'updateProcurement'])->name('update')->middleware('can:manage-raw-material-purchases');
    });

    // Route::group(['prefix' => 'manufacture-unit', 'as' => 'manufactureUnit.'], function () {
    //     Route::get('/', [ManufactureUnitController::class, 'index'])->name('index')->middleware('can:view-manufacture-unit,manage-manufacture-unit');
    //     Route::post('/transfer/{stock}', [ManufactureUnitController::class, 'transferForProduction'])->name('transfer')->middleware('can:manage-manufacture-unit');
    //     Route::post('/final-goods-transfer/{stock}', [ManufactureUnitController::class, 'transferFinalGoods'])->name('finalGoods.transfer')->middleware('can:manage-manufacture-unit');
    //     Route::put('/transfer-status/{transaction}', [ManufactureUnitController::class, 'updateTransferStatus'])->name('transfer.status.update')->middleware('can:manage-manufacture-unit');
    //     Route::post('/workflow', [ManufactureUnitController::class, 'storeWorkflow'])->name('workflow.store')->middleware('can:manage-manufacture-unit');
    //     Route::post('/production-output-transfer/{transaction}', [ManufactureUnitController::class, 'transferProducedGoodsToCurrentOutlet'])->name('productionOutput.transfer')->middleware('can:manage-manufacture-unit');
    // });

    Route::group(['prefix' => 'inventory', 'as' => 'inventory.'], function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index')->middleware('can:view-inventory,manage-inventory');
        Route::post('/adjust', [InventoryController::class, 'adjust'])->name('adjust')->middleware('can:manage-inventory');
    });

    Route::group(['prefix' => 'order', 'as' => 'order.'], function () {
        Route::get('/', [OrderController::class, 'index'])->name('index')->middleware('can:view-orders,manage-orders');
        Route::get('/assigned-jobs', [OrderController::class, 'assignedJobs'])->name('assignedJobs')->middleware('can:view-assigned-jobs,manage-orders');
        Route::get('/show/{order}', [OrderController::class, 'show'])->name('show')->middleware('can:view-orders,manage-orders');
        Route::get('/create', [OrderController::class, 'create'])->name('create')->middleware('can:create-orders,manage-orders');
        Route::get('/edit/{order}', [OrderController::class, 'edit'])->name('edit')->middleware('can:create-orders,manage-orders');
        Route::post('/', [OrderController::class, 'store'])->name('store')->middleware('can:create-orders,manage-orders');
        Route::put('/update/{order}', [OrderController::class, 'update'])->name('update')->middleware('can:create-orders,manage-orders');
        Route::put('/delivery-date/{order}', [OrderController::class, 'updateDeliveryDate'])->name('deliveryDate.update')->middleware('can:create-orders,manage-orders');
        Route::post('/customer/resolve', [OrderController::class, 'resolveCustomer'])->name('customer.resolve')->middleware('can:create-orders,manage-orders');
        Route::get('/{order}/bill/customer', [OrderController::class, 'customerBill'])->name('bill.customer')->middleware('can:view-orders,manage-orders');
        Route::get('/{order}/bill/worker', [OrderController::class, 'workerBill'])->name('bill.worker')->middleware('can:view-assigned-jobs,manage-orders');
        Route::get('/{order}/bill/office', [OrderController::class, 'officeBill'])->name('bill.office')->middleware('can:manage-orders');
        Route::put('/payment/{order}', [OrderController::class, 'updatePayment'])->name('payment.update')->middleware('can:manage-orders');
        Route::put('/status/{order}', [OrderController::class, 'updateStatus'])->name('status.update')->middleware('can:manage-orders');
    });

    Route::group(['prefix' => 'task-management', 'as' => 'taskManagement.'], function () {
        Route::get('/', [TaskManagementController::class, 'index'])->name('index')->middleware('can:view-task-management,manage-task-management,manage-orders');
        Route::get('/order/{order}', [TaskManagementController::class, 'orderTasks'])->name('order')->middleware('can:view-task-management,manage-task-management,manage-orders');
        Route::put('/update/{task}', [TaskManagementController::class, 'update'])->name('update')->middleware('can:manage-task-management,manage-orders');
        Route::put('/worker-update/{task}', [TaskManagementController::class, 'workerUpdate'])->name('workerUpdate')->middleware('can:view-assigned-jobs,manage-orders');
        Route::get('/slip/{task}', [TaskManagementController::class, 'slip'])->name('slip')->middleware('can:view-task-management,manage-task-management,view-assigned-jobs,manage-orders');
    });

    Route::group(['prefix' => 'payment-management', 'as' => 'paymentManagement.'], function () {
        Route::get('/', [PaymentManagementController::class, 'index'])->name('index')->middleware('can:view-payment-management,manage-payment-management,manage-orders');
        Route::put('/task/{task}/pay', [PaymentManagementController::class, 'payWorkerTask'])->name('task.pay')->middleware('can:manage-payment-management,manage-orders');
    });

    Route::group(['prefix' => 'garment-type', 'as' => 'garmentType.'], function () {
        Route::get('/', [GarmentTypeController::class, 'index'])->name('index')->middleware('can:view-garment-types,manage-garment-types');
        Route::get('/create', [GarmentTypeController::class, 'create'])->name('create')->middleware('can:create-garment-types,manage-garment-types');
        Route::post('/', [GarmentTypeController::class, 'store'])->name('store')->middleware('can:create-garment-types,manage-garment-types');
        Route::get('/edit/{garmentType}', [GarmentTypeController::class, 'edit'])->name('edit')->middleware('can:edit-garment-types,manage-garment-types');
        Route::put('/update/{garmentType}', [GarmentTypeController::class, 'update'])->name('update')->middleware('can:edit-garment-types,manage-garment-types');
        Route::delete('/delete/{garmentType}', [GarmentTypeController::class, 'destroy'])->name('destroy')->middleware('can:delete-garment-types,manage-garment-types');
        Route::post('/{garmentType}/measurement', [GarmentTypeController::class, 'storeMeasurement'])->name('measurement.store')->middleware('can:edit-garment-types,manage-garment-types');
        Route::put('/{garmentType}/measurement/{measurement}', [GarmentTypeController::class, 'updateMeasurement'])->name('measurement.update')->middleware('can:edit-garment-types,manage-garment-types');
        Route::delete('/{garmentType}/measurement/{measurement}', [GarmentTypeController::class, 'destroyMeasurement'])->name('measurement.destroy')->middleware('can:edit-garment-types,manage-garment-types');
        Route::post('/{garmentType}/tailoring-package', [GarmentTypeController::class, 'storeTailoringPackage'])->name('tailoringPackage.store')->middleware('can:edit-garment-types,manage-garment-types');
        Route::put('/{garmentType}/tailoring-package/{package}', [GarmentTypeController::class, 'updateTailoringPackage'])->name('tailoringPackage.update')->middleware('can:edit-garment-types,manage-garment-types');
        Route::delete('/{garmentType}/tailoring-package/{package}', [GarmentTypeController::class, 'destroyTailoringPackage'])->name('tailoringPackage.destroy')->middleware('can:edit-garment-types,manage-garment-types');
    });
});
