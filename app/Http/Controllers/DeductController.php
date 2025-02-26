<?php

namespace App\Http\Controllers;

use App\Models\Deduct;
use App\Models\Order;
use App\Models\OrderMeal;
use App\Models\RequestedChildMeal;
use Illuminate\Http\Request;
use function Psy\debug;

class DeductController extends Controller
{
    public function store(Request $request,Order $order)
    {
        // \DB::beginTransaction();
        if (!$request->add){

        $result =$order->deducts()->delete();
            // \DB::commit();
            return ['show'=>$result,'order'=>$order->fresh(),'rm'=>true];
        }
         /** @var OrderMeal $mealOrder */
        foreach ($order->mealOrders as $mealOrder){

            /** @var RequestedChildMeal $requestedChildMeal */
            foreach ($mealOrder->requestedChildMeals as $requestedChildMeal){
                Deduct::create(['service_id'=>$requestedChildMeal->childMeal->service->id,'quantity'=>$requestedChildMeal->quantity * $requestedChildMeal->count,'order_id'=>$order->id]);
            }
        }

        // \DB::commit();
        return ['order'=>$order->fresh(),'show'=>true];
    }
}
