<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buffet_juice_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buffet_package_id')->constrained('buffet_packages')->cascadeOnDelete();
            $table->foreignId('buffet_person_option_id')->constrained('buffet_person_options')->cascadeOnDelete();
            // For simplicity now, a text description. Can be expanded later.
            $table->text('description_ar');
            $table->text('description_en')->nullable();
            $table->timestamps();

            $table->unique(['buffet_package_id', 'buffet_person_option_id'], 'package_person_option_juice_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buffet_juice_rules');
    }
};