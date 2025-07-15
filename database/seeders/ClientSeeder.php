<?php

// database/seeders/ClientSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\User;
use Carbon\Carbon;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        // Maka revendeur 1 fotsiny
        $revendeur = User::where('role', 'revendeur')->first();

        if ($revendeur) {
            Client::create([
                'name' => 'Client Test 1',
                'email' => 'client1@example.com',
                'phone' => '0341234567',
                'montant_paye' => 100000,
                'revendeur_id' => $revendeur->id,
                'date_paiement' => Carbon::now()->subDays(2),
            ]);

            Client::create([
                'name' => 'Client Test 2',
                'email' => 'client2@example.com',
                'phone' => '0347654321',
                'montant_paye' => 150000,
                'revendeur_id' => $revendeur->id,
                'date_paiement' => Carbon::now()->subDays(1),
            ]);
        }
    }
}

