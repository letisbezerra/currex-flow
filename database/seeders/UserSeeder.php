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
            ['name' => 'Ana Lima',     'email' => 'ana@currex.dev',     'country' => 'Brazil',          'currency_code' => 'BRL'],
            ['name' => 'James Smith',  'email' => 'james@currex.dev',   'country' => 'United Kingdom',  'currency_code' => 'GBP'],
            ['name' => 'Yuki Tanaka',  'email' => 'yuki@currex.dev',    'country' => 'Japan',           'currency_code' => 'JPY'],
            ['name' => 'Priya Patel',  'email' => 'priya@currex.dev',   'country' => 'India',           'currency_code' => 'INR'],
            ['name' => 'Lucas Dupont', 'email' => 'lucas@currex.dev',   'country' => 'Canada',          'currency_code' => 'CAD'],
        ];

        foreach ($employees as $data) {
            User::create([
                ...$data,
                'password' => Hash::make('password'),
                'role' => UserRole::Employee,
            ]);
        }

        User::create([
            'name' => 'Maria Santos',
            'email' => 'maria@currex.dev',
            'password' => Hash::make('password'),
            'role' => UserRole::Finance,
            'country' => 'Portugal',
            'currency_code' => 'EUR',
        ]);
    }
}
