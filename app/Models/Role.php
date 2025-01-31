<?php
namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'level',  // مستوى الدور
        'priority', // أهمية الدور
        'guard_name',
    ];
}
