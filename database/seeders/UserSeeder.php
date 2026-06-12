<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            ['name' => 'Alice Johnson',  'email' => 'alice@example.com',   'country' => 'United States', 'currency_code' => 'USD'],
            ['name' => 'Carlos Silva',   'email' => 'carlos@example.com',  'country' => 'Brazil',        'currency_code' => 'BRL'],
            ['name' => 'Emma Schmidt',   'email' => 'emma@example.com',    'country' => 'Germany',       'currency_code' => 'EUR'],
            ['name' => 'Hiroshi Tanaka', 'email' => 'hiroshi@example.com', 'country' => 'Japan',         'currency_code' => 'JPY'],
            ['name' => 'Sophie Martin',  'email' => 'sophie@example.com',  'country' => 'France',        'currency_code' => 'EUR'],
        ];

        foreach ($employees as $data) {
            User::create([
                ...$data,
                'password' => Hash::make('password'),
                'role' => UserRole::Employee,
            ]);
        }

        User::create([
            'name' => 'Finance Manager',
            'email' => 'finance@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Finance,
            'country' => 'United Kingdom',
            'currency_code' => 'GBP',
        ]);
    }
}
