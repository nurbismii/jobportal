<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BiodataPengalamanKerja extends Model
{
    use HasFactory;

    protected $table = 'biodata_pengalaman_kerja';

    protected $guarded = [];

    protected $casts = [
        'masih_bekerja' => 'boolean',
    ];

    public function biodata()
    {
        return $this->belongsTo(Biodata::class);
    }
}
