<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class HelpdeskSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $agentRole = Role::firstOrCreate(['name' => 'Agent']);

        // Pastikan admin yang kamu pakai login tetap jadi Admin role
        $admin = User::where('email', 'admin@local.test')->first();

        if (!$admin) {
            $admin = User::create([
                'name' => 'Admin',
                'email' => 'admin@local.test',
                'password' => Hash::make('password123'),
            ]);
        }

        $admin->assignRole($adminRole);

        Category::firstOrCreate(['name' => 'General'], ['sla_hours' => 24]);
        Category::firstOrCreate(['name' => 'Bug'], ['sla_hours' => 12]);
        Category::firstOrCreate(['name' => 'Access'], ['sla_hours' => 8]);
    }
}
