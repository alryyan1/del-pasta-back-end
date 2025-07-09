<?php

namespace App\Http\Controllers;

use App\Models\FoodOrder;
use App\Models\Whatsapp; // Your static helper for customer confirmation
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OnlineOrderController extends Controller
{
    /**
     * Store a new online food order from a customer.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer.name' => 'required|string|max:255',
            'customer.phone' => 'required|string|max:20',
            'customer.address' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:meals,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'order_type' => 'required|string|in:pickup,delivery',
            'state' => 'nullable|string',
            'area' => 'nullable|string',
        ]);

        $order = null;
        DB::transaction(function () use ($validated, &$order) {
            // Calculate total price from the validated items array
            $totalPrice = collect($validated['items'])->sum(function ($item) {
                return $item['price'] * $item['quantity'];
            });

            // Generate a unique order number
            $orderNumber = 'WEB-' . Carbon::now()->format('ymd-His');

            // Create the main order record
            $order = FoodOrder::create([
                'order_number' => $orderNumber,
                'customer_name' => $validated['customer']['name'],
                'customer_phone' => $validated['customer']['phone'],
                'customer_address' => $validated['customer']['address'],
                'total_price' => $totalPrice,
                'status' => 'pending',
                'order_type' => $validated['order_type'],
                'state' => $validated['state'],
                'area' => $validated['area'],
            ]);

            // Create the associated order items
            foreach ($validated['items'] as $item) {
                $order->items()->create([
                    'meal_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }
        });

        // Send WhatsApp Notification to the restaurant manager
        if ($order) {
            try {
                $order->load('items.meal'); // Eager load relations for the message
                $messageToManager = $this->formatOrderForWhatsapp($order);
                $managerPhone = '78622990'; // Your business/manager number

                $waController = new WaController();
                $waController->sendTextMessage($managerPhone, $messageToManager);
            } catch (\Exception $e) {
                // Log the error but don't fail the entire request
                Log::error('WhatsApp notification failed for Online Order ID ' . $order->id . ': ' . $e->getMessage());
            }
        }

        // Return the newly created order object to the frontend
        return response()->json(['status' => true, 'order' => $order], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(FoodOrder $online_order)
    {
        return response()->json(['data' => $online_order->load('items.meal')]);
    }
    /**
     * Helper method to format the order details into a WhatsApp-friendly message.
     */
    private function formatOrderForWhatsapp(FoodOrder $order): string
    {
        $nl = "\n"; // Newline character
        $message  = "*طلب أونلاين جديد*" . $nl . $nl;
        $message .= "*رقم الطلب:* " . $order->order_number . $nl;
        $message .= "*العميل:* " . $order->customer_name . $nl;
        $message .= "*الهاتف:* " . $order->customer_phone . $nl;
        if($order->customer_address) {
            $message .= "*العنوان:* " . $order->customer_address . $nl;
        }
        $message .= "-----------------" . $nl;
        $message .= "*الطلبات:*" . $nl;

        foreach ($order->items as $item) {
            $message .= "*- (" . $item->quantity . "x) " . $item->meal->name . "*" . $nl;
        }
        
        $message .= "-----------------" . $nl;
        $message .= "*الإجمالي:* " . number_format($order->total_price, 3) . " OMR" . $nl;

        return $message;
    }
    /**
     * Display a paginated listing of the resource.
     */
    public function index(Request $request)
    {
        $query = FoodOrder::with('items.meal') // Eager load items and their meal details
            ->orderBy('created_at', 'desc');

        // Add filtering logic
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('order_number', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('customer_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('customer_phone', 'LIKE', "%{$searchTerm}%");
            });
        }

        return $query->paginate(15);
    }

 /**
     * Update the status and/or delivery fee of the specified order.
     */
    public function update(Request $request, FoodOrder $online_order)
    {
        // Debug logging
        Log::info('OnlineOrder Update Request', [
            'order_id' => $online_order->id,
            'request_data' => $request->all(),
            'url' => $request->fullUrl()
        ]);
        
        $validated = $request->validate([
            'status' => 'sometimes|required|string|in:pending,confirmed,preparing,delivered,cancelled',
            'delivery_fee' => 'sometimes|required|numeric|min:0',
        ]);

        $originalStatus = $online_order->status;
        $online_order->update($validated);

        // If status is changed to 'confirmed', send the payment request message.
        // We use 'confirmed' as the trigger status.
        if (isset($validated['status']) && $validated['status'] === 'confirmed' && $originalStatus !== 'confirmed') {
            try {
                $paymentMessage = $this->formatPaymentRequestMessage($online_order);
                $waController = new WaController();
                $waController->sendTextMessage($online_order->customer_phone, $paymentMessage);
            } catch (\Exception $e) {
                Log::error('WhatsApp payment request failed for Order ID ' . $online_order->id . ': ' . $e->getMessage());
            }
        }

        return response()->json(['status' => true, 'order' => $online_order]);
    }
     /**
     * Remove the specified resource from storage.
     */
        public function destroy(FoodOrder $online_order)
    {
        $online_order->delete();
        return response()->json(['status' => true, 'message' => 'Order deleted successfully.']);
    }
    private function formatPaymentRequestMessage(FoodOrder $order): string
    {
        $nl = "\n";
        $totalPayable = $order->total_price + $order->delivery_fee;

        $message  = "مرحباً " . $order->customer_name . "," . $nl;
        $message .= "تم تأكيد طلبك رقم *" . $order->id . "* وهو الآن قيد التجهيز." . $nl . $nl;
        $message .= "*تفاصيل الدفع:*" . $nl;
        $message .= "مبلغ الطلب: " . number_format($order->total_price, 3) . " OMR" . $nl;
        $message .= "رسوم التوصيل: " . number_format($order->delivery_fee, 3) . " OMR" . $nl;
        $message .= "*الإجمالي للدفع: " . number_format($totalPayable, 3) . " OMR*" . $nl . $nl;
        $message .= "يرجى تحويل المبلغ إلى حساب البنك التالي:" . $nl;
        $message .= "*رقم الحساب: YOUR_ACCOUNT_NUMBER*" . $nl . $nl; // REPLACE WITH YOUR ACTUAL ACCOUNT NUMBER
        $message .= "الرجاء إرسال إيصال التحويل لتأكيد الطلب بشكل نهائي والبدء في التجهيز. شكراً لك!";

        return $message;
    }
}