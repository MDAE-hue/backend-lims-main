<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasFactory;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'department_id',
        'npk',
        'job_title',
        'superior',
    ];

    public $timestamps = true;

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    public function superiorUser()
    {
        return $this->belongsTo(User::class, 'superior');
    }

    public function hasRole($roleName)
    {
        if (is_array($roleName)) {
            foreach ($roleName as $r) {
                if ($this->roles->pluck('name')->contains($r)) {
                    return true;
                }
            }

            return false;
        }

        return $this->roles->pluck('name')->contains($roleName);
    }
}
