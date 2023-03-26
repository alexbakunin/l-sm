<?php

namespace App\Models\Author24;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $table      = 'directory_a24_accounts';
    public    $timestamps = false;
    protected $connection = 'crm';
    protected $guarded = ['password'];
    protected $fillable = ['email', 'password', 'name', 'token', 'user_id'];

    public function settings()
    {
        return $this->hasOne(Settings::class);
    }
}
