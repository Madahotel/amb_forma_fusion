<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'pays',
        'revendeur_id',
        'statut_paiement',
        'montant_paye',
        'date_paiement',
    ];

    // Relation avec le revendeur (User)
    public function revendeur()
    {
        return $this->belongsTo(User::class, 'revendeur_id');
    }

    // Relation avec toutes les transactions
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function latestTransaction()
    {
        return $this->hasOne(Transaction::class)->latestOfMany('date_validation');
        
       
        // return $this->hasOne(Transaction::class)->latestOfMany('created_at');
    }
}
