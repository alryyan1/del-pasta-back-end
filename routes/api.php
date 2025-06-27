<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuffetAdminController;
use App\Http\Controllers\BuffetController;
use App\Http\Controllers\BuffetJuiceRuleAdminController;
use App\Http\Controllers\BuffetPersonOptionAdminController;
use App\Http\Controllers\BuffetStepAdminController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RequestedChildMealController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::post('login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);
Route::post('signup', [AuthController::class, 'signup']);
Route::get('users', [AuthController::class, 'users']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::apiResource('meals', MealController::class);
Route::apiResource('costs',\App\Http\Controllers\CostController::class);
Route::apiResource('CostCategories',\App\Http\Controllers\CostCategoryController::class);
Route::apiResource('orderMeals',\App\Http\Controllers\OrderMealsController::class);
Route::post('RequestedChild/{orderMeal}',[RequestedChildMealController::class,'store']);
Route::patch('RequestedChild/{requestedChildMeal}',[RequestedChildMealController::class,'update']);
Route::post('RequestedChildAddAll/{orderMeal}',[RequestedChildMealController::class,'storeAll']);
Route::get('ordersInfoGraphic',[\App\Http\Controllers\OrderMealsController::class,'ordersInfoGraphic']);
Route::apiResource('customers',\App\Http\Controllers\CustomerController::class);
Route::apiResource('mealReservations',\App\Http\Controllers\CustomerController::class);
Route::get('info',[\App\Http\Controllers\CustomerController::class,'info']);
Route::apiResource('reservations', ReservationController::class);
Route::apiResource('childMeals', \App\Http\Controllers\ChildMealController::class);
Route::post('settings',[SettingsController::class,'update']);
Route::get('settings',[SettingsController::class,'index']);
Route::get('services',[\App\Http\Controllers\ServiceController::class,'index']);
Route::patch('services/{service}',[\App\Http\Controllers\ServiceController::class,'update']);
Route::post('services',[\App\Http\Controllers\ServiceController::class,'store']);
Route::post('defineServices/{meal}',[\App\Http\Controllers\ServiceController::class,'defineServices']);
Route::get('categories',[\App\Http\Controllers\CategoryController::class,'index']);
Route::patch('categories/{category}',[\App\Http\Controllers\CategoryController::class,'update']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/categories', [\App\Http\Controllers\CategoryController::class, 'store']);
});

Route::middleware('auth:sanctum')->group(function (){
    Route::apiResource('orders', OrderController::class);

});
Route::post('orderConfirmed/{order}',[OrderController::class,'orderConfirmed']);
Route::post('orders/pagination/{page}',[OrderController::class,'pagination']);
Route::get('orders/pagination/{page}',[OrderController::class,'pagination']);

Route::get('/printSale',[\App\Http\Controllers\PDFController::class,'printSale']);
Route::post('orderMealsStats',[\App\Http\Controllers\OrderController::class,'orderMealsStats']);
Route::post('send/{order}',[\App\Http\Controllers\OrderController::class,'send']);
Route::post('sendMsg/{order}',[\App\Http\Controllers\OrderController::class,'sendMsg']);
Route::post('deposits',[\App\Http\Controllers\DepositController::class,'store']);
Route::post('deducts/{order}',[\App\Http\Controllers\DeductController::class,'store']);
Route::get('arrival',[\App\Http\Controllers\OrderController::class,'arrival']);
Route::patch('arrival/{order}',[\App\Http\Controllers\OrderController::class,'notify']);

Route::get('orderById/{order}',[OrderController::class,'orderById']);

Route::post('saveImage/{meal}',[MealController::class,'saveImage']);
Route::get('fileNames',[MealController::class,'getFileNamesFromPublicFolder']);
Route::post('sendMsgWa/{order}',[\App\Http\Controllers\WaController::class,'sendMsg']);
Route::post('sendMsgWaLocation/{order}',[\App\Http\Controllers\WaController::class,'sendLocation']);
Route::post('sendMsgWaDocument/{order}',[\App\Http\Controllers\WaController::class,'senDocument']);


Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::apiResource('buffet-packages', BuffetAdminController::class);
    
    Route::post('/buffet-packages/{package}/person-options', [BuffetPersonOptionAdminController::class, 'store']);
    Route::put('/buffet-person-options/{personOption}', [BuffetPersonOptionAdminController::class, 'update']);
    Route::delete('/buffet-person-options/{personOption}', [BuffetPersonOptionAdminController::class, 'destroy']);

    Route::post('/buffet-packages/{package}/steps', [BuffetStepAdminController::class, 'store']);
    Route::put('/buffet-steps/{step}', [BuffetStepAdminController::class, 'update']);
    Route::delete('/buffet-steps/{step}', [BuffetStepAdminController::class, 'destroy']);

    Route::post('/buffet-person-options/{personOption}/juice-rule', [BuffetJuiceRuleAdminController::class, 'storeOrUpdate']);
});

    // New Route for managing the juice rule FOR a person option
    Route::post('/buffet-person-options/{personOption}/juice-rule', [BuffetJuiceRuleAdminController::class, 'storeOrUpdate']);
// --- New Buffet Configuration Routes ---
Route::prefix('buffet')->group(function () {
    Route::get('/packages', [BuffetController::class, 'getPackages']);
    Route::get('/packages/{package}/person-options', [BuffetController::class, 'getPersonOptions']);
    Route::get('/packages/{package}/steps', [BuffetController::class, 'getSteps']);
    Route::get('/person-options/{personOption}/juice-info', [BuffetController::class, 'getJuiceInfo']);
    Route::apiResource('buffet-packages', BuffetAdminController::class);

});
