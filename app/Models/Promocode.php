<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Promocode extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'promocodes';

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
        $this->active = false;
        $this->first_order = false;
        $this->orders_count = false;
        $this->customers_count = false;
        $this->customer_source = '{}';
        $this->customer_source_category = '{}';
        $this->office = '{}';
        $this->order_min_cost = false;
        $this->order_type = '{}';
        $this->summ = true;
        parent::__construct($attributes);
    }

    public function order(): HasOne
    {
        return $this->hasOne('App\Models\Order');
    }

    public function settings(): BelongsTo
    {
        return $this->belongsTo('App\Models\PromocodeSettings');
    }

}
