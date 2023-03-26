<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderCashBack extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders_cash_back';

    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'crm';

    /**
     * @var string[]
     */
    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        $this->value = false;
        $this->hold = true;
        $this->canceled = false;
        parent::__construct($attributes);
    }


    public function order(): HasOne
    {
        return $this->hasOne('App\Models\Order');
    }

}
