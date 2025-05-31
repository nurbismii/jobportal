<?php

namespace App\Models\Hris;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departemen extends Model
{
    use HasFactory;

    protected $connection = 'mysql_hris';
    protected $table = 'departemens';

    protected $guarded = [];
}
