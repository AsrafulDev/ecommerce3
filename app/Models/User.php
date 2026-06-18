<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    // ✅ এটা যোগ করো
    protected $guard_name = 'admin';  // 👈 গুরুত্বপূর্ণ লাইন

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'image',
        'role',
    ];

    /**
     * Get the employee record for this user.
     */
    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id');
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Laravel 12: Use casts() method instead of $casts property
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }
}
