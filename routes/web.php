<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CostController;
use App\Http\Controllers\CostItemController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebHookController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UltraMsgController;
use App\Http\Controllers\PDFController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Test UltraMsg from web routes
Route::get('/ultramsg/test-send', [UltraMsgController::class, 'testSend']);
// Online order invoice (PDF)
Route::get('/online-orders/{online_order}/invoice', [PDFController::class, 'PrintOnlineSale']);

Route::get('/ordersAi',[\App\Http\Controllers\PDFController::class,'ordersAi']);
Route::get('/orders',[\App\Http\Controllers\PDFController::class,'orders']);
Route::get('/print',[\App\Http\Controllers\PDFController::class,'printSale']);

//users
Route::get('/users',[UserController::class,'create']);
Route::get('/users/{userId}',[UserController::class,'destroy']);
Route::post('/createUSer',[UserController::class,'store']);
Route::post('/updateUser',[UserController::class,'update']);

//export order to excel
Route::get('/exportExcel',[OrderController::class,'exportExcel']);

//items
Route::get('/items',[ItemController::class,'index']);
Route::get('/items/{itemId}',[ItemController::class,'destroy']);
Route::post('/createItem',[ItemController::class,'store']);
Route::post('/updateItem',[ItemController::class,'update']);

//costs
Route::get('/costs',[CostItemController::class,'index']);
Route::get('/destroyCost/{costId}',[CostItemController::class,'destroy']);
Route::get('/costs/create',[CostItemController::class,'create']);
Route::post('/costs',[CostItemController::class,'store']);


Route::get('/itemReport',[CostItemController::class,'itemReport']);


//suppliers
Route::get('/suppliers',[SupplierController::class,'index']);
Route::get('/suppliers/{supplierId}',[SupplierController::class,'destroy']);
Route::post('/createSupplier',[SupplierController::class,'store']);
Route::post('/updateSupplier',[SupplierController::class,'update']);

Route::post('webhook',[WebhookController::class,'webhook']);
Route::get('convert_images',function (){
   $meals =  \App\Models\Meal::get();
   /** @var \App\Models\Meal $meal */
    foreach ($meals as $meal){
       $mealController = new \App\Http\Controllers\MealController();
       $mealController->saveImage(null,$meal);
   }
});
Route::get('convert_categories_images',function (){
   $cats =  \App\Models\Category::get();
   /** @var \App\Models\Category $category */
    foreach ($cats as $category){
       $mealController = new \App\Http\Controllers\MealController();
       $mealController->saveImage(null,$category);
   }
});
