<?php

namespace App\Http\Controllers;

namespace App\Http\Controllers;

use App\Exports\ExportOrder;
use App\Models\BuffetOrderSelection;
use App\Models\BuffetPackage;
use App\Models\BuffetPersonOption;
use App\Models\Deduct;
use App\Models\Deposit;
use App\Models\Order;
use App\Models\Meal;
use App\Models\OrderMeal;
use App\Models\Settings;
use App\Models\Whatsapp;
use Carbon\Carbon;
use DB;
use Hamcrest\Core\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
    
    /**
     * Create a new order (handles both standard and buffet orders).
     */
    public function storeOrderBoffet(Request $request)
    {
        // Handle Buffet Order
        if ($request->input('is_buffet_order')) {
            return $this->storeBuffetOrder($request);
        }

        // Handle Standard (à la carte) Order (your existing logic)
        $today = Carbon::today();
        $user = auth()->user();
        /** @var Order $lastOrder */
        $lastOrder = Order::whereDate('created_at', '=', $today)->orderByDesc('id')->first();
        $new_number = $lastOrder ? $lastOrder->order_number + 1 : 1;

        $order = Order::create([
            'order_number' => $new_number,
            'user_id' => $user->id,
            'delivery_date' => $today,
            'draft' => ' '
        ]);

        return response()->json([
            'status' => true, // Changed for consistency
            'data' => $order->load(['mealOrders.meal', 'mealOrders'])
        ], 201);
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

   
    public function store(Request $request)
    {
        $today = Carbon::today();
        $user = auth()->user();
        
        $lastOrder = Order::whereDate('created_at', '=', $today)->orderByDesc('id')->first();
        $new_number = $lastOrder ? $lastOrder->order_number + 1 : 1;

        $order = Order::create([
            'order_number' => $new_number,
            'user_id' => $user->id,
            'delivery_date' => $today,
            'status' => 'pending', // Sensible default
            'draft' => ' '
        ]);

        return response()->json([
            'status' => true,
            'data' => $order->load(['mealOrders.meal', 'customer'])
        ], 201);
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

      /**
     * Private method to handle storing a new buffet order.
     */
    private function storeBuffetOrder(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'delivery_date' => 'required|date',
            'delivery_time' => 'required',
            'notes' => 'nullable|string',
            'buffet_package_id' => 'required|exists:buffet_packages,id',
            'buffet_person_option_id' => 'required|exists:buffet_person_options,id',
            'selections' => 'required|array',
            'selections.*.buffet_step_id' => 'required|exists:buffet_steps,id',
            'selections.*.meal_id' => 'required|exists:meals,id',
        ]);

        $package = BuffetPackage::with('steps')->findOrFail($validated['buffet_package_id']);
        $personOption = BuffetPersonOption::findOrFail($validated['buffet_person_option_id']);
        
        $selectionsByStep = collect($validated['selections'])->groupBy('buffet_step_id');

        foreach ($package->steps as $step) {
            $count = $selectionsByStep->get($step->id, collect())->count();
            if ($count < $step->min_selections || $count > $step->max_selections) {
                throw ValidationException::withMessages([
                    'selections' => "For step '{$step->title_ar}', you must select between {$step->min_selections} and {$step->max_selections} items. You selected {$count}."
                ]);
            }
        }
        
        $order = null;
        DB::transaction(function () use ($validated, $personOption, &$order) {
            $today = Carbon::today();
            $user = auth()->user();
            $lastOrder = Order::whereDate('created_at', '=', $today)->orderByDesc('id')->first();
            $new_number = $lastOrder ? $lastOrder->order_number + 1 : 1;

            $order = Order::create([
                'order_number' => $new_number,
                'user_id' => $user->id,
                'customer_id' => $validated['customer_id'],
                'delivery_date' => $validated['delivery_date'],
                'delivery_time' => $validated['delivery_time'],
                'notes' => $validated['notes'],
                'is_buffet_order' => true,
                'buffet_package_id' => $validated['buffet_package_id'],
                'buffet_person_option_id' => $validated['buffet_person_option_id'],
                'buffet_base_price' => $personOption->price,
                'amount_paid' => 0, 
                'draft' => ' -'
            ]);

            foreach ($validated['selections'] as $selection) {
                BuffetOrderSelection::create([
                    'order_id' => $order->id,
                    'buffet_step_id' => $selection['buffet_step_id'],
                    'meal_id' => $selection['meal_id'],
                ]);
            }
        });

        if ($order) {
            try {
                $order->load(['customer', 'buffetPackage', 'buffetPersonOption', 'buffetSelections.meal', 'buffetSelections.buffetStep']);
                $message = $this->formatBuffetOrderForWhatsapp($order);
                $restaurantPhone = '78622990'; 
                Whatsapp::sendMsgWb($restaurantPhone, $message);
            } catch (\Exception $e) {
                \Log::error('Failed to send WhatsApp notification for order ID ' . $order->id . ': ' . $e->getMessage());
            }
        }

        return response()->json([
            'status' => true,
            'data' => $order
        ], 201);
    }

    /**
     * Formats the buffet order details into a string for WhatsApp.
     */
    private function formatBuffetOrderForWhatsapp(Order $order): string
    {
        $nl = "\n";
        $message  = "*طلب بوفيه جديد تلقائي*" . $nl . $nl;
        $message .= "----------------------------------" . $nl;
        $message .= "*رقم الطلب:* " . $order->order_number . $nl;
        $message .= "*الباقة:* " . $order->buffetPackage->name_ar . $nl;
        $message .= "*عدد الأشخاص:* " . $order->buffetPersonOption->label_ar . $nl;
        $message .= "*السعر الأساسي:* " . number_format($order->buffet_base_price, 3) . " OMR" . $nl . $nl;

        $message .= "*بيانات العميل:*" . $nl;
        $message .= "- *الاسم:* " . $order->customer->name . $nl;
        $message .= "- *الهاتف:* " . $order->customer->phone . $nl;
        $message .= "*تاريخ ووقت التسليم:*" . $nl . Carbon::parse($order->delivery_date)->format('d-m-Y') . " @ " . Carbon::parse($order->delivery_time)->format('h:i A') . $nl . $nl;

        $message .= "----------------------------------" . $nl;
        $message .= "*الاختيارات:*" . $nl;

        $selectionsByStep = $order->buffetSelections->groupBy('buffetStep.title_ar');

        foreach ($selectionsByStep as $stepTitle => $selections) {
            $message .= $nl . "*" . $stepTitle . "*:" . $nl;
            foreach ($selections as $selection) {
                $message .= "- " . $selection->meal->name . $nl;
            }
        }
        
        if (!empty($order->notes)) {
            $message .= $nl . "----------------------------------" . $nl;
            $message .= "*ملاحظات إضافية:*" . $nl . $order->notes;
        }

        return $message;
    }

}
