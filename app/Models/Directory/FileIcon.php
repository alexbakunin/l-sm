<?php

namespace App\Models\Directory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileIcon extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'directory_files_icons';

    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'crm';


}
