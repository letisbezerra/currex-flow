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
            ['name' => 'Ana Lima',     'email' => 'ana@example.com',     'country' => 'Brazil',          'currency_code' => 'BRL'],
            ['name' => 'James Smith',  'email' => 'james@example.com',   'country' => 'United Kingdom',  'currency_code' => 'GBP'],
            ['name' => 'Yuki Tanaka',  'email' => 'yuki@example.com',    'country' => 'Japan',           'currency_code' => 'JPY'],
            ['name' => 'Priya Patel',  'email' => 'priya@example.com',   'country' => 'India',           'currency_code' => 'INR'],
            ['name' => 'Lucas Dupont', 'email' => 'lucas@example.com',   'country' => 'Canada',          'currency_code' => 'CAD'],
        ];

        foreach ($employees as $data) {
            User::create([
                ...$data,
                'password' => Hash::make('password'),
                'role'     => UserRole::Employee,
            ]);
        }

        User::create([
            'name'          => 'Finance Manager',
            'email'         => 'finance@buzzvel.com',
            'password'      => Hash::make('password'),
            'role'          => UserRole::Finance,
            'country'       => 'Portugal',
            'currency_code' => 'EUR',
        ]);
    }
}
