<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workout_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order');
            $table->unsignedTinyInteger('target_sets')->nullable();
            $table->string('target_reps')->nullable(); // "8-12", "AMRAP", "5"
            $table->decimal('target_weight', 6, 2)->nullable();
            $table->unsignedInteger('rest_seconds')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_exercises');
    }
};
