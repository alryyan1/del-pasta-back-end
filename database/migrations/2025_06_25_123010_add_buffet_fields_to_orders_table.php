<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_buffet_order')->default(false)->after('id');
            $table->foreignId('buffet_package_id')->nullable()->after('is_buffet_order')->constrained('buffet_packages')->nullOnDelete();
            $table->foreignId('buffet_person_option_id')->nullable()->after('buffet_package_id')->constrained('buffet_person_options')->nullOnDelete();
            $table->decimal('buffet_base_price', 10, 3)->nullable()->after('buffet_person_option_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['buffet_package_id']);
            $table->dropForeign(['buffet_person_option_id']);
            $table->dropColumn(['is_buffet_order', 'buffet_package_id', 'buffet_person_option_id', 'buffet_base_price']);
        });
    }
};