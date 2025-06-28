<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RequestedChildMealController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Buffet Controllers
use App\Http\Controllers\BuffetController;
use App\Http\Controllers\BuffetAdminController;
use App\Http\Controllers\BuffetPersonOptionAdminController;
use App\Http\Controllers\BuffetStepAdminController;
use App\Http\Controllers\BuffetJuiceRuleAdminController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// --- AUTHENTICATION & PUBLIC ROUTES ---
Route::post('login', [AuthController::class, 'login']);
Route::post('signup', [AuthController::class, 'signup']);

// --- PUBLIC-FACING BUFFET ROUTES (for the ordering wizard) ---
Route::prefix('buffet')->name('buffet.')->group(function () {
    Route::get('/packages', [BuffetController::class, 'getPackages'])->name('packages');
    Route::get('/packages/{buffet_package}/person-options', [BuffetController::class, 'getPersonOptions'])->name('person-options');
    Route::get('/packages/{buffet_package}/steps', [BuffetController::class, 'getSteps'])->name('steps');
    Route::get('/person-options/{buffet_person_option}/juice-info', [BuffetController::class, 'getJuiceInfo'])->name('juice-info');
});


// --- AUTHENTICATED ROUTES ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // --- ADMIN PANEL BUFFET CONFIGURATION ROUTES ---
    Route::prefix('admin')->name('admin.')->group(function () {
        // Buffet Package Management (Full CRUD)
        Route::apiResource('buffet-packages', BuffetAdminController::class);
        
        // Person/Price Options Management
        Route::post('/buffet-packages/{package}/person-options', [BuffetPersonOptionAdminController::class, 'store'])->name('packages.person-options.store');
        Route::put('/buffet-person-options/{personOption}', [BuffetPersonOptionAdminController::class, 'update'])->name('person-options.update');
        Route::delete('/buffet-person-options/{personOption}', [BuffetPersonOptionAdminController::class, 'destroy'])->name('person-options.destroy');

        // Buffet Steps Management
        Route::post('/buffet-packages/{buffet_package}/steps', [BuffetStepAdminController::class, 'store'])->name('packages.steps.store');
        Route::put('/buffet-steps/{buffet_step}', [BuffetStepAdminController::class, 'update'])->name('steps.update');
        Route::delete('/buffet-steps/{buffet_step}', [BuffetStepAdminController::class, 'destroy'])->name('steps.destroy');

        // Juice Rule Management
        Route::post('/buffet-person-options/{personOption}/juice-rule', [BuffetJuiceRuleAdminController::class, 'storeOrUpdate'])->name('person-options.juice-rule');
    });

    // --- Other Authenticated Routes ---
    Route::post('/categories', [\App\Http\Controllers\CategoryController::class, 'store']);
    Route::apiResource('orders', OrderController::class);
    // ... other authenticated routes
});


// --- OTHER EXISTING ROUTES ---
Route::get('users', [AuthController::class, 'users']);
Route::apiResource('meals', MealController::class);
Route::apiResource('costs', \App\Http\Controllers\CostController::class);
Route::apiResource('CostCategories', \App\Http\Controllers\CostCategoryController::class);
Route::apiResource('orderMeals', \App\Http\Controllers\OrderMealsController::class);
Route::post('RequestedChild/{orderMeal}', [RequestedChildMealController::class, 'store']);
Route::patch('RequestedChild/{requestedChildMeal}', [RequestedChildMealController::class, 'update']);
Route::post('RequestedChildAddAll/{orderMeal}', [RequestedChildMealController::class, 'storeAll']);
Route::get('ordersInfoGraphic', [\App\Http\Controllers\OrderMealsController::class, 'ordersInfoGraphic']);
Route::apiResource('customers', \App\Http\Controllers\CustomerController::class);
Route::get('info', [\App\Http\Controllers\CustomerController::class, 'info']);
Route::apiResource('reservations', ReservationController::class);
Route::apiResource('childMeals', \App\Http\Controllers\ChildMealController::class);
Route::post('settings', [SettingsController::class, 'update']);
Route::get('settings', [SettingsController::class, 'index']);
Route::get('services', [\App\Http\Controllers\ServiceController::class, 'index']);
Route::patch('services/{service}', [\App\Http\Controllers\ServiceController::class, 'update']);
Route::post('services', [\App\Http\Controllers\ServiceController::class, 'store']);
Route::post('defineServices/{meal}', [\App\Http\Controllers\ServiceController::class, 'defineServices']);
Route::get('categories', [\App\Http\Controllers\CategoryController::class, 'index']);
Route::patch('categories/{category}', [\App\Http\Controllers\CategoryController::class, 'update']);
Route::post('orderConfirmed/{order}', [OrderController::class, 'orderConfirmed']);
Route::post('orders/pagination/{page}', [OrderController::class, 'pagination']);
Route::get('orders/pagination/{page}', [OrderController::class, 'pagination']);
Route::get('/printSale', [\App\Http\Controllers\PDFController::class, 'printSale']);
Route::post('orderMealsStats', [OrderController::class, 'orderMealsStats']);
Route::post('send/{order}', [OrderController::class, 'send']);
Route::post('sendMsg/{order}', [OrderController::class, 'sendMsg']);
Route::post('deposits', [\App\Http\Controllers\DepositController::class, 'store']);
Route::post('deducts/{order}', [\App\Http\Controllers\DeductController::class, 'store']);
Route::get('arrival', [OrderController::class, 'arrival']);
Route::patch('arrival/{order}', [OrderController::class, 'notify']);
Route::get('orderById/{order}', [OrderController::class, 'orderById']);
Route::post('saveImage/{meal}', [MealController::class, 'saveImage']);
Route::get('fileNames', [MealController::class, 'getFileNamesFromPublicFolder']);
Route::post('sendMsgWa/{order}', [\App\Http\Controllers\WaController::class, 'sendMsg']);
Route::post('sendMsgWaLocation/{order}', [\App\Http\Controllers\WaController::class, 'sendLocation']);
Route::post('sendMsgWaDocument/{order}', [\App\Http\Controllers\WaController::class, 'senDocument']);