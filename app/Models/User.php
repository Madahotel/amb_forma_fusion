<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str; 

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'solde'];
    

    protected $hidden = ['password', 'remember_token'];

    // Relation
    public function clients()
    {
        return $this->hasMany(Client::class, 'revendeur_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'revendeur_id');
    }
    protected static function booted()
    {
        static::creating(function ($user) {
            if ($user->role === 'revendeur' && !$user->code_affiliation) {
                $user->code_affiliation = strtoupper(Str::random(8));
            }
        });
    }

}