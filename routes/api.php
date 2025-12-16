<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

Route::get('/payments/methods', [PaymentController::class, 'methods']);
Route::post('payments/result', [PaymentController::class, 'result']);

/*
|--------------------------------------------------------------------------
| Blog (public)
|--------------------------------------------------------------------------
*/
Route::get('/blog/posts', [BlogController::class, 'index']);
Route::get('/blog/posts/{id}', [BlogController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Protected
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Products
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // Cart
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::put('/cart/update/{id}', [CartController::class, 'update']);
    Route::delete('/cart/remove/{id}', [CartController::class, 'remove']);

    // Orders
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);

    // Payments
    Route::post('/payments/initiate', [PaymentController::class, 'initiate']);
    Route::post('/payments/verify/{transactionId}', [PaymentController::class, 'verify']);

    // Blog (protected)
    Route::post('/blog/posts', [BlogController::class, 'store']);
    Route::put('/blog/posts/{id}', [BlogController::class, 'update']);
    Route::delete('/blog/posts/{id}', [BlogController::class, 'destroy']);

    // Vendor
    Route::get('/vendor/dashboard', [VendorController::class, 'dashboard']);
    Route::get('/vendor/orders', [OrderController::class, 'vendorOrders']);
    Route::get('/vendor/analytics', [VendorController::class, 'analytics']);
    Route::get('/vendors/{id}/products', [VendorController::class, 'products']);
    Route::get('/vendors/products', [VendorController::class, 'myProducts']);
    Route::get('/vendors/nearby', [VendorController::class, 'nearby']);
    Route::get('/vendors/products/{id}', [ProductController::class, 'vendorShow']);

    // Admin
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/admin/users', [AdminController::class, 'users']);
    Route::put('/admin/users/{id}/status', [AdminController::class, 'updateUserStatus']);
    Route::get('/admin/vendors', [AdminController::class, 'vendors']);
    Route::post('/admin/vendors/{id}/approve', [AdminController::class, 'approveVendor']);
    Route::post('/admin/vendors/{id}/reject', [AdminController::class, 'rejectVendor']);
    Route::get('/admin/orders', [AdminController::class, 'orders']);
    Route::delete('/admin/products/{id}', [AdminController::class, 'destroy']);

    // Chat
    Route::get('/chat/conversations', [ChatController::class, 'index']);
    Route::post('/chat/conversations', [ChatController::class, 'store']);
    Route::get('/chat/conversations/{id}/messages', [ChatController::class, 'messages']);
    Route::post('/chat/conversations/{id}/messages', [ChatController::class, 'send']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
});


// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

