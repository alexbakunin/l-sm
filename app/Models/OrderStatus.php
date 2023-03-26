<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'directory_orders_status';

    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'crm';

}
