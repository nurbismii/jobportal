<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VhirePkwtContractHistory extends Model
{
    protected $table = 'vhire_pkwt_contract_histories';

    protected $guarded = [];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function contract()
    {
        return $this->belongsTo(VhirePkwtContract::class, 'contract_id');
    }
}
