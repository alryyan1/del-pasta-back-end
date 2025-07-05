<?php

namespace App\Http\Controllers;

use App\Models\BuffetOrder;
use App\Models\BuffetPackage;
use App\Models\BuffetPersonOption;
use App\Models\Customer;
use App\Models\Whatsapp; // Your existing static helper
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BuffetOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = BuffetOrder::with(['customer', 'buffetPackage', 'buffetPersonOption'])
            ->orderBy('delivery_date', 'desc')
            ->orderBy('delivery_time', 'desc');
    
        // Filter by customer name or phone
        $query->when($request->search, function ($q, $search) {
            $q->whereHas('customer', function ($customerQuery) use ($search) {
                $customerQuery->where('name', 'like', "%{$search}%")
                              ->orWhere('phone', 'like', "%{$search}%");
            });
        });
        
        // Filter by customer state
        $query->when($request->state, function ($q, $state) {
            $q->whereHas('customer', function ($customerQuery) use ($state) {
                $customerQuery->where('state', 'like', "%{$state}%");
            });
        });
    
        // Filter by delivery date
        $query->when($request->date, function ($q, $date) {
            // Ensure date is valid before using it
            try {
                $formattedDate = Carbon::parse($date)->format('Y-m-d');
                $q->whereDate('delivery_date', $formattedDate);
            } catch (\Exception $e) {
                // Ignore invalid date formats
            }
        });
    
        return $query->orderBy('id', 'desc')->paginate(15);
    }
    /**
     * Store a newly created buffet order.
     * Handles creating a new customer if they don't exist.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer' => 'required|array',
            'customer.name' => 'required|string|max:255',
            'customer.phone' => 'required|string|max:20',
            'customer.address' => 'nullable|string|max:255', // Add validation for the new address field

            'delivery_date' => 'required|date_format:Y-m-d',
            'delivery_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string',
            'buffet_package_id' => 'required|exists:buffet_packages,id',
            'buffet_person_option_id' => 'required|exists:buffet_person_options,id',
            'selections' => 'required|array',
            'selections.*.buffet_step_id' => 'required|exists:buffet_steps,id',
            'selections.*.meal_id' => 'required|exists:meals,id',
        ]);

        $package = BuffetPackage::with('steps')->findOrFail($validated['buffet_package_id']);
        $personOption = BuffetPersonOption::findOrFail($validated['buffet_person_option_id']);
        
        // --- Validate that the number of selections matches the rules for each step ---
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
        // Use a database transaction to ensure all or no data is saved.
        DB::transaction(function () use ($validated, $personOption, &$order) {
            // Find an existing customer by phone number, or create a new one.
            $customer = Customer::firstOrCreate(
                ['phone' => $validated['customer']['phone']],
                ['name' => $validated['customer']['name'], 'address' => $validated['customer']['address']]
            );

            // Generate a unique order number
            $orderNumber = 'BO-' . Carbon::now()->format('ymd') . '-' . (BuffetOrder::count() + 1);

            // Create the main buffet order record
            $order = BuffetOrder::create([
                'order_number' => $orderNumber,
                'user_id' => auth()->id() ?? 1, // Fallback to user 1 if not authenticated (e.g., from public page)
                'customer_id' => $customer->id,
                'delivery_date' => $validated['delivery_date'],
                'delivery_time' => $validated['delivery_time'],
                'notes' => $validated['notes'],
                'buffet_package_id' => $validated['buffet_package_id'],
                'buffet_person_option_id' => $validated['buffet_person_option_id'],
                'base_price' => $personOption->price,
                'status' => 'pending', // Initial status
            ]);

            // Create the selections for the order
            foreach ($validated['selections'] as $selection) {
                $order->selections()->create([
                    'buffet_step_id' => $selection['buffet_step_id'],
                    'meal_id' => $selection['meal_id'],
                ]);
            }
        });

        // --- Send WhatsApp Notifications ---
        if ($order) {
            try {
                // Eager load all necessary data for the message
                $order->load(['customer', 'buffetPackage', 'buffetPersonOption', 'selections.meal', 'selections.buffetStep']);
                $messageToManager = $this->formatBuffetOrderForWhatsapp($order);

                $buffetManagerPhone = '78622990'; // Your business/manager number
                $customerPhone = $order->customer->phone;

                $waController = new WaController();
                
                // 1. Send detailed order to the manager
                $waController->sendTextMessage($buffetManagerPhone, $messageToManager);

                // 2. Send a simple confirmation to the customer
                $customerMessage = "شكراً لطلبك من ديل باستا! طلب البوفيه الخاص بك رقم " . $order->order_number . " قيد التأكيد. سنتواصل معك قريباً.";
                // Assuming your static method handles country code logic
                $waController->sendTextMessage($customerPhone, $customerMessage);

            } catch (\Exception $e) {
                // Log the error but don't fail the entire request
                Log::error('WhatsApp notification failed for Buffet Order ID ' . $order->id . ': ' . $e->getMessage());
            }
        }

        return response()->json(['status' => true, 'data' => $order], 201);
    }
    
    /**
     * Display the specified buffet order with all its details.
     */
    public function show(BuffetOrder $buffetOrder)
    {
        // Eager load all relationships needed for the details view
        return $buffetOrder->load([
            'customer', 
            'buffetPackage', 
            'buffetPersonOption', 
            'selections.meal', 
            'selections.buffetStep'
        ]);
    }

    /**
     * Update the status of the specified buffet order.
     */
    public function update(Request $request, BuffetOrder $buffetOrder)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,confirmed,delivered,cancelled',
        ]);

        $buffetOrder->update(['status' => $validated['status']]);

        // Return the updated order with its relationships for immediate UI update
        return response()->json($buffetOrder->load(['customer', 'buffetPackage', 'buffetPersonOption']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BuffetOrder $buffetOrder)
    {
        $buffetOrder->delete();
        return response()->json(['message' => 'Buffet order deleted successfully.']);
    }
    
    /**
     * Helper method to format the order details into a WhatsApp-friendly message.
     */
    private function formatBuffetOrderForWhatsapp(BuffetOrder $order): string
    {
        $nl = "\n"; // Newline character
        $message  = "*طلب بوفيه جديد*" . $nl . $nl;
        $message .= "*رقم الطلب:* " . $order->order_number . $nl;
        $message .= "*الباقة:* " . $order->buffetPackage->name_ar . $nl;
        $message .= "*عدد الأشخاص:* " . $order->buffetPersonOption->label_ar . $nl;
        $message .= "*السعر:* " . number_format($order->base_price, 3) . " OMR" . $nl . $nl;
        $message .= "*العميل:* " . $order->customer->name . " (" . $order->customer->phone . ")" . $nl;
        $message .= "*التسليم:* " . $order->delivery_date->format('d-m-Y') . " @ " . Carbon::parse($order->delivery_time)->format('h:i A') . $nl . $nl;
        $message .= "*الاختيارات:*" . $nl;
        
        // Group selections by step for a cleaner message
        $selectionsByStep = $order->selections->groupBy('buffetStep.title_ar');
        foreach ($selectionsByStep as $stepTitle => $selections) {
            $message .= $nl . "*- " . $stepTitle . ":*" . $nl;
            foreach ($selections as $selection) {
                $message .= "  • " . $selection->meal->name . $nl;
            }
        }
        
        if (!empty($order->notes)) {
            $message .= $nl . "*ملاحظات:*\n" . $order->notes;
        }

        return $message;
    }
}