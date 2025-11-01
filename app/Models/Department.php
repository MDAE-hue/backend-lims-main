<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'departments';

    protected $fillable = ['name', 'head_id'];

    public $timestamps = false;

    public function users()
    {
        return $this->hasMany(User::class, 'department_id');
    }
}
