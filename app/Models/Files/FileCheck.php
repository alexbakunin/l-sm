<?php

namespace App\Models\Files;

use Illuminate\Database\Eloquent\Model;

class FileCheck extends Model
{
    protected $table      = 'files_check';
    protected $connection = 'crm';

    protected $fillable = [
        'order_id',
        'file_id',
        'author_id',
        'external_id',
        'status',
        'result',
        'report_link',
        'job_id',
        'job_status'
    ];

}
