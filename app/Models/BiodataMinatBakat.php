<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BiodataMinatBakat extends Model
{
    use HasFactory;

    public const TYPES = ['hobi', 'bakat'];
    public const CATEGORIES = ['olahraga', 'seni', 'lainnya'];

    protected $table = 'biodata_minat_bakat';

    protected $guarded = [];

    public function biodata()
    {
        return $this->belongsTo(Biodata::class);
    }
}
