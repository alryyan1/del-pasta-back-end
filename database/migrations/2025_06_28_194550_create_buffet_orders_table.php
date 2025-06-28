<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buffet_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('user_id')->comment('The admin/staff who placed the order');
            
            $table->foreignId('buffet_package_id')->constrained('buffet_packages')->cascadeOnDelete();
            $table->foreignId('buffet_person_option_id')->constrained('buffet_person_options')->cascadeOnDelete();
            
            $table->decimal('base_price', 10, 3);
            $table->date('delivery_date');
            $table->time('delivery_time');
            $table->text('notes')->nullable();
            
            $table->string('status')->default('pending'); // pending, confirmed, delivered, cancelled
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buffet_orders');
    }
};