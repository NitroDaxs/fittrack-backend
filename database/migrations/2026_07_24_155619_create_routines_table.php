<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('routines', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('goal'); // Casted to RoutineGoal Enum on model
            $table->string('level'); // Casted to Difficulty Enum on model
            $table->unsignedTinyInteger('days_per_week');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routines');
    }
};
