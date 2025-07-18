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

    protected $fillable = ['name', 'email', 'password', 'role', 'solde', 'pays', 'code_affiliation'];



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
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if ($user->role === 'revendeur' && empty($user->code_affiliation)) {
                do {
                    $code = strtoupper(Str::random(6));
                } while (User::where('code_affiliation', $code)->exists());

                $user->code_affiliation = $code;
            }
        });
    }
public function getAffiliationLinkAttribute()
{
    $baseUrl = config('app.frontend_url', 'https://tonsite.com'); // manampy default raha tsy amboarina
    return $baseUrl . '/register?ref=' . $this->code_affiliation;
}

}
