<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            
            // Customer Information (denormalized for simplicity, as customers might not have an account)
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->text('customer_address')->nullable();
            
            $table->text('notes')->nullable();
            $table->decimal('total_price', 10, 3);
            $table->string('status')->default('pending'); // pending, confirmed, preparing, out_for_delivery, delivered, cancelled
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_orders');
    }
};