<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->text('customer_address')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('total_price', 10, 3);
            $table->decimal('delivery_fee', 10, 3)->default(0);
            $table->string('status')->default('pending');
            $table->string('order_type')->default('delivery');
            $table->string('state')->nullable();
            $table->string('area')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_orders');
    }
};


