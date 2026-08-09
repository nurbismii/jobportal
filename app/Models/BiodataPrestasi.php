<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BiodataPrestasi extends Model
{
    use HasFactory;

    public const FIELDS = ['Akademik', 'Non-akademik', 'Olahraga', 'Seni', 'Lainnya'];

    public const LEVELS = [
        'Sekolah/Kampus',
        'Kecamatan',
        'Kabupaten/Kota',
        'Provinsi',
        'Nasional',
        'Internasional',
    ];

    protected $table = 'biodata_prestasi';

    protected $guarded = [];

    public function biodata()
    {
        return $this->belongsTo(Biodata::class);
    }
}
