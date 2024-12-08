<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
   
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',             //            $table->enum('role', ['manager', 'general_manager', 'hr']);

        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // علاقة مع الموظفين (من قام بإضافة الموظف)
    public function employees()
    {
        return $this->hasMany(Employee::class, 'added_by');
    }

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
        ];
    }

     /**
     * تحقق مما إذا كان المستخدم لديه الدور المحدد.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }
}
