<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lowongan extends Model
{
    use HasFactory;

    protected $table = 'lowongan';

    protected $guarded = [];

    public function lamarans()
    {
        return $this->hasMany(Lamaran::class, 'loker_id');
    }
}
