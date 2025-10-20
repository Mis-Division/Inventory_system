<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\CategoriesController;
use App\Http\Controllers\Api\StocksController;
use App\Http\Controllers\Api\ItemsController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require Sanctum token)
//  Route::post('users/create_user', [UserController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {

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
    Route::prefix('stocks')->group(function(){
        Route::post('/createStock', [StocksController::class, 'CreateProductStocks']);
    });

    //itemcode
    Route::prefix('Items')->group(function () {
    Route::post('createCode', [ItemsController::class, 'CreateItemCode']);
    Route::get('getItemCode', [ItemsController::class, 'GetItemCode']);
    Route::get('getItemCode/{id}', [ItemsController::class, 'GetItemCodeId']);
    Route::put('updateItemCode/{id}', [ItemsController::class, 'UpdateItemCode']);
    Route::delete('deleteItemCode/{id}', [ItemsController::class, 'DeleteItemCode']);
    });
});
