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
     * Display the specified resource for the success page.
     */
    public function show(FoodOrder $foodOrder)
    {
        // Return the order with its items and the meal details for each item
        return response()->json(['data' => $foodOrder->load('items.meal')]);
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
}