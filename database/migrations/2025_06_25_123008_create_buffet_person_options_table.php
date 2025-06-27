<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buffet_person_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buffet_package_id')->constrained('buffet_packages')->cascadeOnDelete();
            $table->string('label_ar'); // e.g., "10 - 12 شخص"
            $table->string('label_en')->nullable(); // e.g., "10 - 12 Persons"
            $table->unsignedInteger('min_persons')->default(0);
            $table->unsignedInteger('max_persons')->default(0);
            $table->decimal('price', 10, 3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buffet_person_options');
    }
};