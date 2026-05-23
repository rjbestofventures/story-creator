<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ])->assignRole('admin');

        User::firstOrCreate(['email' => 'support@storycreator.bot'], [
            'name' => 'Support',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ])->assignRole('admin');
    }
}
