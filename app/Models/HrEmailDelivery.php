<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrEmailDelivery extends Model
{
    protected $guarded = [];

    protected $casts = ['sent_at' => 'datetime', 'attachments' => 'array'];
}
