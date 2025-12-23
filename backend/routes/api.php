<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\CategoriesController;
use App\Http\Controllers\Api\ItemsController;
use App\Http\Controllers\Api\DepartmentHeadsController;
use App\Http\Controllers\Api\ReceivingController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\MrvController;
use App\Http\Controllers\Api\StockCOntroller;
use App\Http\Controllers\Api\McrtController;
use App\Http\Controllers\ItemImportController;
use App\Http\Controllers\Api\MctController;
use App\http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\RfmController;


// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require Sanctum token)
//  Route::post('users/create_user', [UserController::class, 'store']);

 Route::post('/create_user', [UserController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
     Route::get('/dept-head', [DepartmentHeadsController::class, 'getDeptHead']);

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard example
    Route::get('/dashboard', function (Request $request) {
        return response()->json([
            'fullname' => $request->user()->fullname,
        ]);
    });

    // Authenticated user info
    Route::middleware('auth:sanctum')->get('/user', [UserController::class, 'getUserModules']);

    // Employee routes
    Route::prefix('employees')->group(function () {

        Route::post('/', [EmployeeController::class, 'registerEmployee']);
        Route::get('/', [EmployeeController::class, 'getEmployee']);
        Route::patch('/{id}', [EmployeeController::class, 'updateEmployee']);
        Route::delete('/{id}', [EmployeeController::class, 'destroy']);
    });

    // User access routes
    Route::prefix('users')->group(function () {
        Route::post('/create_user', [UserController::class, 'store']);
        Route::put('/update_user/{id}', [UserController::class, 'update']);
        Route::get('/display_user/{id}', [UserController::class, 'getUserById']);
        Route::get('/display_user', [UserController::class, 'getAllUsers']);
        Route::delete('/delete_user/{id}', [UserController::class, 'deleteUser']);
    });
    // Supplier routes
    Route::prefix('suppliers')->group(function () {
        Route::post('/create_supplier', [SupplierController::class, 'CreateSupplier']);
        Route::get('/get_supplier', [SupplierController::class, 'GetSuppliers']);
        Route::get('/get_supplier/{id}', [SupplierController::class, 'GetSupplierById']);
        Route::put('/update_supplier/{id}', [SupplierController::class, 'UpdateSupplier']);
        Route::delete('/delete_supplier/{id}', [SupplierController::class, 'DeleteSupplier']);
        Route::post('/import_suppliers', [ItemImportController::class, 'importSupplier']);
    });
    // Category routes
    Route::prefix('categories')->group(function () {
        Route::post('/create_category', [CategoriesController::class, 'CreateCategory']);
        Route::get('/get_category', [CategoriesController::class, 'GetCategories']);
        Route::get('/get_category/{id}', [CategoriesController::class, 'GetCategoryById']);
        Route::put('/update_category/{id}', [CategoriesController::class, 'UpdateCategory']);
        Route::delete('/delete_category/{id}', [CategoriesController::class, 'DeleteCategory']);
    });
    //stocks


    //itemcode
    Route::prefix('Items')->group(function () {
    Route::post('createCode', [ItemsController::class, 'CreateItemCode']);
    Route::get('getItemCode', [ItemsController::class, 'GetItemCode']);
    Route::get('getItemCode/{id}', [ItemsController::class, 'GetItemCodeId']);
    Route::put('updateItemCode/{id}', [ItemsController::class, 'UpdateItemCode']);
    Route::delete('deleteItemCode/{id}', [ItemsController::class, 'DeleteItemCode']);
    Route::get('displayStocks', [ItemsController::class, 'GetItemsForMrv']);
    });

    // Receiving routes
    Route::prefix('receiving')->group(function () {
        Route::post('/receive_items', [ReceivingController::class, 'store']);
        Route::get('/DisplayRR', [ReceivingController::class, 'DisplayRR']);
        Route::get('/DisplayRR/{r_id}', [ReceivingController::class, 'getRRbyId']);
        Route::put('/update_rr/{r_id}', [ReceivingController::class, 'updateRR']);
        Route::delete('/delete_rr/{r_id}', [ReceivingController::class, 'softDeleteRR']);
        Route::get('/getDeletedRR', [ReceivingController::class, 'getSoftDeletedRR']);
        Route::get('/check_item/{po_number}/{ItemCode_id}', [ReceivingController::class, 'check_item']);
        Route::get('/checkPOstatus/{po_number}', [ReceivingController::class, 'checkPOstatus']);
        Route::get('/get_item_order/{po_number}/{ItemCode_id}', [ReceivingController::class, 'get_item_order']);
        Route::get('/checkPOComplete/{po_number}', [ReceivingController::class, 'checkPOComplete']);
    });
    

//Unit Controller
    Route::prefix('Units')->group(function() {
    Route::get('/display', [UnitController::class, 'displayUnit']);
    });

    //MRV Controller to APi
    Route::prefix('Mrv')->group(function() {
        Route::post('MrvCreate',[MrvController::class, 'CreateMrv']);
        Route::get('MrvDisplay', [MrvController::class, 'displayMrv']);
        Route::get('/MrvDisplaytest', [MrvController::class, 'MrvDisplaytest']);
        Route::get('/check-mrv/{rfm_number}', [MrvController::class, 'checkMrvByRfm']);
        Route::get('/showById/{mrv_id}', [MrvController::class, 'showById']);
        Route::put('/updateMrv/{mrv_id}', [MrvController::class, 'update']);
        Route::delete('/deleteMrv/{mrv_id}', [MrvController::class, 'deleteMrv']);
        Route::get('/printMrv/{mrv_id}', [MrvController::class, 'printMrv']);


    });

    Route::prefix('Products')->group(function(){
    Route::get('/list', [StockController::class, 'ledger']); // list all / paginated
    Route::get('/grouped', [StockController::class, 'ledgerGroupedPaginated']); // grouped stocks
    Route::get('/list/{itemCodeId}', [StockController::class, 'ledgerById']); // single item ledger
    });

    //MCRT Controller to API
    Route::prefix('Mcrt')->group(function() {
        Route::post('/McrtCreate',[McrtController::class, 'store']);
        Route::get('/displayMcrt', [McrtController::class, 'showMcrt']);
        Route::get('/displayMcrt/{mcrt_id}', [McrtController::class, 'showMcrtById']);
        Route::put('/updateMcrt/{mcrt_id}', [McrtController::class, 'updateMcrt']);
        Route::delete('/McrtDelete/{mcrt_id}', [McrtController::class, 'deleteMcrt']);
    });
        Route::post('/items/import', [ItemImportController::class, 'import']);

    //MCT Controller to API
    Route::prefix('Mct')->group(function() {
        Route::post('/MctCreate',[MctController::class, 'store']);
        Route::get('/displayMct', [MctController::class, 'displayMct']);
        Route::get('/check-mrv', [MctController::class, 'checkMrvForMct']);

    });

    Route::prefix('CodeAccount')->group(function() {
        Route::get('/Accounts', [AccountController::class, 'getAccountInfo']);
    });

    //Department heads
    Route::prefix('DepartmentHeads')->group(function() {
        Route::get('/heads', [DepartmentHeadsController::class, 'index']);
    });

    //RFM api Module
    Route::prefix('Rfm')->group(function() {
        Route::post('/create', [RfmController::class, 'store']);
        Route::get('/display', [RfmController::class, 'index']);
        Route::get('/display/{rfm_id}', [RfmController::class, 'show']);
        Route::put('/update/{rfm_id}', [RfmController::class, 'update']);
        Route::delete('/delete/{rfm_id}', [RfmController::class, 'destroy']);
        Route::get('/print/{rfm_id}', [RfmController::class, 'print']);
        Route::get('/fetchRfmForMrv/{rfm_number}', [RfmController::class, 'fetchRfmForMrv']);
    });

});
