<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use App\Models\Colocation;
use App\Models\Expense;
use App\Models\Settlement;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role' , 
        'reputation' ,
        'is_banned' ,

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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_banned' => 'boolean',
        ];
    }
    protected static function booted() : void 
    {
        static::creating(function(User $user) {
 //ila luser huwa lwel flquery n3tih l admin
     if (static::query()->count() === 0 ) {
        $user->role = 'admin' ;
     }

        });
    }

    public function colocations() {
    return $this->belongsToMany(colocation::class)
    ->withPivot(['role', 'joined_at', 'left_at'])
    ->withTimestamps();


    }
    public function ownedColocations()
{
    return $this->hasMany(Colocation::class, 'owner_id');
}

public function expensesPaid()
{
    return $this->hasMany(Expense::class, 'payer_id');
}
public function settlementsSent()
{
    return $this->hasMany(Settlement::class, 'from_user_id');
}
public function settlementsReceived()
{
    return $this->hasMany(Settlement::class, 'to_user_id');
}

}
