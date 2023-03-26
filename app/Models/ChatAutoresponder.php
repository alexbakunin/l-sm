<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ChatAutoresponder
 *
 * @property int $id
 * @property int $room_id
 * @property int $message_id
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\ChatAutoresponderFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatAutoresponder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChatAutoresponder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChatAutoresponder query()
 * @method static \Illuminate\Database\Eloquent\Builder|ChatAutoresponder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatAutoresponder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatAutoresponder whereMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatAutoresponder whereRoomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatAutoresponder whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatAutoresponder whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property int $response_message_id
 * @method static \Illuminate\Database\Eloquent\Builder|ChatAutoresponder whereResponseMessageId($value)
 */
class ChatAutoresponder extends Model
{
    use HasFactory;

    public const TYPE_NIGHT = 'night';
    public const TYPE_NORMS = 'norms';
}
