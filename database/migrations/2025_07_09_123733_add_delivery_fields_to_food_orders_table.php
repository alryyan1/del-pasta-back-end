<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_orders', function (Blueprint $table) {
            $table->string('order_type')->default('pickup')->after('status')->comment('pickup or delivery');
            $table->string('state')->nullable()->after('order_type')->comment('Governorate/State for delivery');
            $table->string('area')->nullable()->after('state')->comment('Area/Wilayat for delivery');
        });
    }

    public function down(): void
    {
        Schema::table('food_orders', function (Blueprint $table) {
            $table->dropColumn(['order_type', 'state', 'area']);
        });
    }
};