<?php

namespace App\Models\Hris;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Peringatan extends Model
{
    use HasFactory;

    protected $connection = 'mysql_hris';
    protected $table = 'sp_report';
    protected $guarded = [];
}
