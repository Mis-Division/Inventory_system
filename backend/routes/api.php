<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Supplier;
use App\Http\Controllers\Api\Categories;

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
        Route::post('/create_supplier', [Supplier::class, 'CreateSupplier']);
        Route::get('/get_supplier', [Supplier::class, 'GetSuppliers']);
        Route::get('/get_supplier/{id}', [Supplier::class, 'GetSupplierById']);
        Route::put('/update_supplier/{id}', [Supplier::class, 'UpdateSupplier']);
        Route::delete('/delete_supplier/{id}', [Supplier::class, 'DeleteSupplier']);
    });
    // Category routes
    Route::prefix('categories')->group(function () {
        Route::post('/create_category', [Categories::class, 'CreateCategory']);
        Route::get('/get_category', [Categories::class, 'GetCategories']);
        Route::get('/get_category/{id}', [Categories::class, 'GetCategoryById']);
        Route::put('/update_category/{id}', [Categories::class, 'UpdateCategory']);
        Route::delete('/delete_category/{id}', [Categories::class, 'DeleteCategory']);
    });
});
