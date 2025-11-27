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
        Route::get('MrvDisplay/{mrv_id}', [MrvController::class, 'gerMrvDetails']);
    });
    

});
