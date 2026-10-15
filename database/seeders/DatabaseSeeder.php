<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Comptes de démonstration (mot de passe : "Password123" — à changer en production).
        $accounts = [
            ['Administrateur', 'admin@example.com', Role::Admin],
            ['L. Diasse (évaluateur)', 'ldiasse@groupeisi.com', Role::Admin],
            ['Marie Gestionnaire', 'manager@example.com', Role::Manager],
            ['Paul Comptable', 'comptable@example.com', Role::Accountant],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'password' => 'Password123',
                'role' => $role,
                'email_verified_at' => now(),
            ]);
        }
    }
}
