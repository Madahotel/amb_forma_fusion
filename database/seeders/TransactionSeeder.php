<?php

// database/seeders/TransactionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $revendeur = User::where('role', 'revendeur')->first();
        $admin = User::where('role', 'admin')->first();

        if ($revendeur && $admin) {
            Transaction::create([
                'revendeur_id' => $revendeur->id,
                'montant' => 30000,
                'moyen_paiement' => 'mvola',
                'statut' => 'valide',
                'date_demande' => Carbon::now()->subDays(1),
                'date_validation' => Carbon::now(),
                'admin_id_validator' => $admin->id,
                'note' => 'Paiement validé manuellement',
            ]);

            Transaction::create([
                'revendeur_id' => $revendeur->id,
                'montant' => 40000,
                'moyen_paiement' => 'banque',
                'statut' => 'en_attente',
                'date_demande' => Carbon::now(),
            ]);
        }
    }
}

