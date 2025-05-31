<?php

namespace App\Models\Hris;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $connection = 'mysql_hris';
    protected $table = 'employees';
    protected $primaryKey = 'nik_karyawan';
    public $incrementing = false;
    protected $guarded = [];

    public function getDivisi()
    {
        return $this->hasOne(Divisi::class, 'id', 'divisi_id');
    }
}
