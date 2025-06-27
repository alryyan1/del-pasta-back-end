<?php

use App\Models\Category; // Import Category model
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buffet_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buffet_package_id')->constrained('buffet_packages')->cascadeOnDelete();
            $table->unsignedInteger('step_number');
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->text('instructions_ar')->nullable();
            $table->text('instructions_en')->nullable();
            $table->foreignIdFor(Category::class)->comment('Category of meals for this step');
            $table->unsignedInteger('min_selections')->default(1);
            $table->unsignedInteger('max_selections')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['buffet_package_id', 'step_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buffet_steps');
    }
};