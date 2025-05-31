<?php

namespace App\Models\Hris;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelurahan extends Model
{
    use HasFactory;

    protected $connection = 'mysql_hris';
    protected $table = 'master_kelurahan';

    protected $guarded = [];
}
