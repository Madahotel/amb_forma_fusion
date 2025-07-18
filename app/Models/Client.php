<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'pays','phone', 'montant_paye', 'revendeur_id', 'date_paiement'];


public function revendeur()
{
    return $this->belongsTo(User::class, 'revendeur_id');
}
public function transactions()
{
    return $this->hasMany(Transaction::class);
}


    
}

