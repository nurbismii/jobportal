<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lamaran extends Model
{
    use HasFactory;

    protected $table = 'lamaran';

    protected $guarded = [];

    public function lowongan()
    {
        return $this->hasOne(Lowongan::class, 'id', 'lowongan_id');
    }

    public function biodata()
    {
        return $this->hasOne(Biodata::class, 'id', 'biodata_id');
    }
}
