<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exercise_user', function (Blueprint $table) {
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('saved_at')->useCurrent();
            $table->primary(['exercise_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_user');
    }
};
