<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Comptes de démonstration. Mot de passe par défaut "Password123" ; en production,
        // SEED_PASSWORD doit être défini explicitement pour ne jamais exposer ce défaut public.
        $password = env('SEED_PASSWORD', 'Password123');

        if (app()->environment('production') && $password === 'Password123') {
            throw new \RuntimeException('Définissez SEED_PASSWORD avant de lancer le seeder en production.');
        }

        $accounts = [
            ['Administrateur', 'admin@example.com', Role::Admin],
            ['L. Diasse (évaluateur)', 'ldiasse@groupeisi.com', Role::Admin],
            ['Marie Gestionnaire', 'manager@example.com', Role::Manager],
            ['Paul Comptable', 'comptable@example.com', Role::Accountant],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'password' => $password,
                'role' => $role,
                'email_verified_at' => now(),
            ]);
        }

        if (! app()->environment('production')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
