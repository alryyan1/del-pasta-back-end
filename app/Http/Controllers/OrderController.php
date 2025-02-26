<?php

namespace App\Http\Controllers;

namespace App\Http\Controllers;

use App\Exports\ExportOrder;
use App\Models\Deduct;
use App\Models\Deposit;
use App\Models\Order;
use App\Models\Meal;
use App\Models\OrderMeal;
use App\Models\Settings;
use App\Models\Whatsapp;
use Carbon\Carbon;
use Hamcrest\Core\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PDO;
use PHPUnit\TextUI\XmlConfiguration\Logging\TestDox\Text;

class OrderController extends Controller
{
    public function orderById(Request $request,Order $order)
    {
        return $order;

    }
    public function notify(Request $request, Order $order)
    {
        if ($request->get('outside')== 1){
            $order->car_palette = $request->get('car_palette'); ;
            $order->outside =1 ;
            $order->outside_confirmed =1 ;
            $order->save();
            $order = $order->fresh();
            $name = $order->customer->name;
            $details = $order->orderMealsNames();
            $msg = <<<Text
========================
  العميل في الخارج
   Customer is outside
========================
         Order $order->id

   🧑🏻‍🦱$name

   🚗 $order->car_palette

   👕  $details


Text;
            $settings = Settings::first();
            $worker_phone= $settings->inventory_notification_number;
            Whatsapp::sendMsgWb($worker_phone,$msg);
        }
    }
    public function arrival(Request $request)
    {
       return   Order::where('outside','=',1)->get();


    }
    public function pagination(Request $request, $page)
    {
//        \DB::enableQueryLog();
        $query = Order::query();
        $query->with('mealOrders.meal');
        $name = $request->get('name');
        $query->when($request->name,function (Builder $q) use ($name){
            $q->whereHas('customer',function ( $q)  use($name){
                $q->where('name','like',"%$name%")->orWhere('area','like',"%$name%")->orWhere('phone','like',"%$name%")->orWhere('state','like',"%$name%");
            });
        });
        $query->when($request->status,function (Builder $q) use ($request) {
                $q->where('status','=',$request->status);
        });
        $query->when($request->id,function (Builder $q) use ($request) {
            $q->where('id','=',$request->id);
    });
        $query->when($request->date,function (Builder $q) use ($request){
            $date = $request->date;
                $q->whereRaw('Date(created_at) = ?',[$date]);
        });
        $query->when($request->get('state'), function (Builder $q) use ($request) {
            $q->whereHas('customer', function ($query) use ($request) {
                $state = $request->get('state');
                $query->where('state', 'Like', "%$state%");
            });
        });
        $query->when($request->get('city'), function (Builder $q) use ($request) {
            $city  =  $request->get('city');
            
            $q->whereHas('customer', function ($query) use ($city) {
                $query->where('area', 'LIKE',"%$city%");
            });
        });
//        return ['data'=> $query->orderByDesc('id')->paginate($page) , 'analytics'=> \DB::getQueryLog()];
        return $query->orderByDesc('id')->paginate($page);


    }
    public function send(Request $request,Order $order)
    {
        if ($order->customer == null){
            return response()->json(['status'=>false,'message'=>'Customer Must Be Selected'],404);
        }
        $meals_names = $order->orderMealsNames();
        $totalPrice = $order->totalPrice();
//        $msg = <<<TEXT
//اهلاً وسهلاً ،
//اختيارك يشرّفنا، تجربة مميزة ولذيذة إن شاء الله .
//
//
//تكرماً : إيداع المبلغ لإعتماد الطلب
//
//0345043777450011
//منى العيسائي
//بنك مسقط
//
//تحويل سريع
//95519234
//
//الفاتوره  $totalPrice ريال
//TEXT;

      return  Whatsapp::sendPdf($request->get('base64'),$order->customer->phone);

    }
    public function sendMsg(Request $request,Order $order)
    {
        if ($order->customer == null){
            return response()->json(['status'=>false,'message'=>'Customer Must be Selected'],404);
        }
        $meals_names = $order->orderMealsNames();
        $totalPrice = $order->totalPrice();
        /** @var Settings $settings */
        $settings = Settings::first();
        $msg = $settings->header_content;
        Whatsapp::sendLocation($order->customer->phone);

      return  Whatsapp::sendMsgWb($order->customer->phone,$msg);

    }
    public function orderMealsStats(Request $request)
    {
        $pdo = \DB::getPdo();
        $filter = '';
        $date =  $request->get('date');
        if ($date){
            $filter = " WHERE orders.delivery_date = '$date'";
        }elseif ($request->get('category')){
            $filter.='Where c.id = '.$request->get('category');
        }
//        $query = ;
        $data =  $pdo->query("SELECT s.id as serviceId, s.name as childName,   SUM(child_meals.quantity * requested_child_meals.count) as totalQuantity FROM `requested_child_meals`
    JOIN child_meals  on child_meals.id = requested_child_meals.child_meal_id
    join order_meals  on order_meals.id = requested_child_meals.order_meal_id
    join meals  on meals.id = child_meals.meal_id
    join orders on orders.id = order_meals.order_id
    join  categories c on c.id = meals.category_id
    join services s on s.id = child_meals.service_id
                                         $filter   GROUP by s.name,s.id")->fetchAll();

        $arr = [];
        foreach ($data as $d){
            $serviceId =  $d['serviceId'];
            $quantity_sum =  Deposit::where('service_id','=',$serviceId)->sum('quantity');
            $quantity_deducted_sum =  Deduct::where('service_id','=',$serviceId)->sum('quantity');
            $d['totalDeposit'] = $quantity_sum;
            $d['totalDeduct'] = $quantity_deducted_sum;
            $arr[]=$d;
//            print_r($d);
        }
//        \DB::table('requested_child_meals')
        return response()->json($arr);
    }
    public function orderConfirmed(Request $request , Order $order)
    {

    }
    // Get all orders
    public function index(Request $request)
    {
        if ($request->query('today')) {
            $today = Carbon::today();
            return Order::with(['mealOrders.meal','mealOrders'=>function ($q) {
                $q->with('requestedChildMeals.orderMeal');
            }])->whereDate('created_at', $today)->orderByDesc('id')->get();

        } else {
            return Order::with('mealOrders.meal')->orderByDesc('id')->get();

        }
    }

    // Create a new order
    public function store(Request $request)
    {
        $today = Carbon::today();
        $user = auth()->user();
        /** @var Order $lastOrder */
        $lastOrder = Order::whereDate('created_at', '=', $today)->orderByDesc('id')->first();
        $new_number = 1;
        if ($lastOrder) {
            $new_number = $lastOrder->order_number + 1;
        }
        $order = Order::create(['order_number' => $new_number, 'user_id' => $user->id,'delivery_date'=>$today,'draft'=>' ']);

        return response()->json(['status' => $order, 'data' => $order->load(['mealOrders.meal','mealOrders'])], 201,);
    }

    // Update order status
    public function updateStatus(Request $request, $id)
    {
        $order = Order::find($id);
        $order->update(['status' => $request->status]);
        return response()->json($order, 200);
    }

    public function update(Request $request, Order $order)
    {


        if ($request->get('status')=='Completed'){
            $name = $order->customer->name;
            $msg = <<<Text
 عزيزي العميل  $name
 نفيدك باغراضك جاهزه للاستلام
 نتمنى أن تكون الخدمة التي قدمناها قد نالت إعجابكم
 شكرا لاختيارك لنا
Text;

            Whatsapp::sendMsgWb($order->customer->phone,$msg);

            $msg = <<<Text
 عزيزي العميل
 عند وصولك للمحل اضغط علي الرابط ادناه لاخطار استقبال المحل بوصولك
 https://rain-laundry.com/#arrive/$order->id

Text;
            Whatsapp::sendMsgWb($order->customer->phone,$msg);

            $pdfC = new PDFController();
            //send invoice using whatsapp
//            $request->order_id = $order->id;
            $pdfC->printSale($request,$order->id,true);
        }
        if (intval($request->amount_paid ) > $order->totalPrice()){
            return response()->json(['status'=>false,'message'=>'Bad operation'],404);
        }
        if ($request->get('order_confirmed')) {
        }
            if ($request->get('order_confirmed')){

            if ($order->customer == null){
                return response()->json(['status'=>false,'message'=>'Customer must be selected'],404);
            }
            if ($order->status == 'cancelled'){
                return response()->json(['status'=>false,'message'=>'Please Change Status First'],404);
            }
//            return  $request->get('amount_paid');


       //     $order->amount_paid = $order->totalPrice();
            $order->status = 'confirmed';


        }elseif ($request->get('status') == 'cancelled'){
            $order->order_confirmed = 0;
                $order->amount_paid = 0;
                $order->delivery_fee = 0;

        }elseif ($request->get('status') == 'delivered'){
//                   $order->amount_paid = $order->totalPrice();
                   $order->update(['amount_paid'=>$order->totalPrice()]);
//                   return ['show'=>true,'message'=>'shifjsidfjodf'];


        }


        $result = $order->update($request->all());
        return response()->json(['status' => $result, 'order' => $order->load('customer'),'show'=>$order->order_confirmed == true], 200);
    }


    // Get a specific order
    public function show($id)
    {
        return Order::find($id);
    }


    public function destroy(Request $request ,Order $order)
    {
        return ['status'=>$order->delete()];
    }

    public function exportExcel()
    {
        return Excel::download(new ExportOrder, 'orders.xlsx');
    }

}
