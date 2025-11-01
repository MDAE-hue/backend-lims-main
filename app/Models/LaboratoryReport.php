<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaboratoryReport extends Model
{
    protected $table = 'laboratory_report';

    protected $fillable = [
        'no_report', 'requested_by', 'department_id', 'location', 'status',
        'date_sampling', 'date_analysis', 'remark', 'sampler', 'analyst',
        'reviewed_by', 'acknowledge_by', 'reviewed_at', 'description',
    ];
}
