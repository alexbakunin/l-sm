<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 *
 */
class Order extends Model
{
    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'crm';

    public $timestamps = false;
    protected $fillable = ['theme', 'course_id', 'type_of_work', 'deadline', 'originality', 'originality_proc', 'font_size', 'font_interval', 'note', 'pages_count'];

    public function promocode(): BelongsTo
    {
        return $this->belongsTo('App\Models\Promocode');
    }

    public function cashBack(): BelongsTo
    {
        return $this->belongsTo('App\Models\OrderCashBack');
    }

}
