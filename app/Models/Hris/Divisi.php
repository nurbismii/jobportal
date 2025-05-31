<?php

namespace App\Models\Hris;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Divisi extends Model
{
    use HasFactory;

    protected $connection = 'mysql_hris';
    protected $table = 'divisis';

    protected $guarded = [];

    public function getDepartemen()
    {
        return $this->hasOne(Departemen::class, 'id', 'departemen_id');
    }
}
