<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->longText('instructions')->nullable();
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced']);
            $table->enum('exercise_type', ['strength', 'cardio', 'mobility', 'balance']);
            $table->string('video_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
