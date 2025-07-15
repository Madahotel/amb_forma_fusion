<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = ['revendeur_id', 'montant', 'moyen_paiement', 'statut', 'date_demande', 'date_validation', 'admin_id_validator', 'note'];

    public function revendeur()
    {
        return $this->belongsTo(User::class, 'revendeur_id');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'admin_id_validator');
    }
}
