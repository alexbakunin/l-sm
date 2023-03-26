<?php

namespace App\Models\Author24;

use App\Scopes\ExternalServices\Author24Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $connection = 'crm';
    protected $table      = 'external_orders';
    public    $timestamps = false;
    protected $fillable   = ['order_id', 'source', 'can_be_estimated', 'estimated', 'price', 'data', 'account_id',
        'bid_id', 'our_order_id', 'last_message_id', 'last_update', 'active', 'type_of_work', 'course',
        'deadline_notify', 'status', 'inwork', 'last_notification_id'];

    protected $casts = [
        'data' => 'collection'
    ];

    protected static function booted()
    {
        static::addGlobalScope(new Author24Scope);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function ourOrder(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Order::class, 'our_order_id', 'id');
    }
}
