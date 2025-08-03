<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Panel;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
    use Spatie\Permission\Models\Permission;


class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
   
    use HasApiTokens, HasFactory, Notifiable,HasRoles,HasPanelShield;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        // 'role',             //            $table->enum('role', ['manager', 'general_manager', 'hr']);

        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];


public function permissions()
{
    return $this->morphToMany(
        Permission::class,
        'model',
        'model_has_permissions',
        'model_id',
        'permission_id'
    );
}


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
    // public function hasRole(string $role): bool
    // {
    //     return $this->role === $role;
    // }

//     public function role()
// {
//     return $this->belongsTo(Role::class);
// }
// public function roles()
// {
//     return $this->belongsToMany(Role::class);
// }


    // ✅ **علاقة المستخدم بالأدوار عبر Spatie**
    // public function roles()
    // {
    //     return $this->belongsToMany(\Spatie\Permission\Models\Role::class, 'model_has_roles', 'model_id', 'role_id')
    //         ->where('model_type', self::class);
    // }

    // ✅ **تمكين Filament Shield عبر Spatie Laravel Permissions**
     // ✅ **علاقة المستخدم بالأدوار عبر Spatie**
  
 
     // ✅ **تمكين Filament Shield عبر Spatie Laravel Permissions**
     public function canAccessPanel(Panel $panel): bool
     {
        //  return $this->hasPermissionTo('access-filament');
        return true;
     }
}
