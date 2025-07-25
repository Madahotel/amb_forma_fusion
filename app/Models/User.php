<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str; // N'oubliez pas d'importer Str

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'solde',
        'pays',
        'code_affiliation',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        // Assurez-vous que 'solde' est un float si vous stockez des décimales
        'solde' => 'float', 
    ];

    /**
     * Define the relationship with clients.
     * A user can have many clients if they are a 'revendeur'.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clients()
    {
        return $this->hasMany(Client::class, 'revendeur_id');
    }

    /**
     * Define the relationship with transactions.
     * A user (revendeur) can have many transactions (commissions).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'revendeur_id');
    }

    /**
     * The "booted" method of the model.
     * This is where you define model event listeners.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically generate a unique affiliation code for 'revendeur' role
        static::creating(function ($user) {
            if ($user->role === 'revendeur' && empty($user->code_affiliation)) {
                $user->code_affiliation = self::generateUniqueAffiliationCode();
            }
        });
    }

    /**
     * Generate a unique 6-character uppercase affiliation code.
     *
     * @return string
     */
    public static function generateUniqueAffiliationCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (self::where('code_affiliation', $code)->exists());

        return $code;
    }

    /**
     * Get the affiliation link attribute.
     * This is an accessor that appends the affiliation code to the frontend URL.
     *
     * @return string
     */
    public function getAffiliationLinkAttribute(): string
    {
        // Get the frontend URL from config, with a fallback
        $baseUrl = config('app.frontend_url', 'https://tonsite.com'); 
        return $baseUrl . '/register?ref=' . $this->code_affiliation;
    }
}
