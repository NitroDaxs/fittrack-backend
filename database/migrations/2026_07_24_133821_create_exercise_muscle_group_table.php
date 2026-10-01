<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exercise_muscle_group', function (Blueprint $table) {
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('muscle_group_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['primary', 'secondary'])->default('primary');
            $table->primary(['exercise_id', 'muscle_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_muscle_group');
    }
};
