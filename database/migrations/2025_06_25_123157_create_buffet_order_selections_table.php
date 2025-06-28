<?php

use App\Models\Meal; // Import Meal model
use App\Models\Order; // Import Order model
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buffet_order_selections', function (Blueprint $table) {
            $table->id();
            $table->renameColumn('order_id', 'buffet_order_id');
            // Add the new foreign key constraint
            $table->foreign('buffet_order_id')->references('id')->on('buffet_orders')->cascadeOnDelete();
            $table->foreignId('buffet_step_id')->constrained('buffet_steps')->cascadeOnDelete();
            $table->foreignIdFor(Meal::class)->constrained()->cascadeOnDelete();
            // $table->unsignedInteger('quantity')->default(1); // Not explicitly needed per user story for buffet items, but could be added.
            $table->timestamps();

            // A user might select the same meal multiple times if a step allows it (max_selections > 1 for the same item)
            // If each selected item for a step must be unique, add a unique constraint:
            // $table->unique(['order_id', 'buffet_step_id', 'meal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buffet_order_selections');
    }
};