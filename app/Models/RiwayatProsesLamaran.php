<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiwayatProsesLamaran extends Model
{
    use HasFactory;

    protected $table = 'riwayat_proses_lamaran';

    protected $guarded = [];

    public function scopeAktifBekerja(Builder $query): Builder
    {
        return $query->whereRaw('LOWER(status_proses) = ?', ['aktif bekerja']);
    }
}
