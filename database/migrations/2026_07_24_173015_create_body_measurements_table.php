<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('body_measurements'); // Dropped to ensure smooth refresh

        Schema::create('body_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('body_fat_pct', 4, 1)->nullable();
            $table->json('custom_measurements')->nullable(); // e.g. {"waist": 82, "chest": 105, "arms": 38}
            $table->string('photo_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('body_measurements');
    }
};
