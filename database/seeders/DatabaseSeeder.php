<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'role' => UserRole::ADMIN,
            'password' => 'password123',
        ]);

        User::factory()->create([
            'name' => 'John Member',
            'email' => 'member@test.com',
            'role' => UserRole::MEMBER,
            'password' => 'password123',
        ]);
        $this->call([
            ExerciseSeeder::class,
            RoutineSeeder::class,
            ArticleSeeder::class,
        ]);
    }
}
