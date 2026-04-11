<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountRecoveryRequest extends Model
{
    use HasFactory;

    protected $table = 'account_recovery_requests';

    protected $fillable = [
        'user_id',
        'no_ktp',
        'requested_name',
        'requested_email',
        'requested_phone',
        'requested_notes',
        'registered_name',
        'registered_email',
        'registered_phone',
        'registered_birth_date',
        'status',
        'processed_by',
        'processed_at',
        'approved_email',
        'admin_notes',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'registered_birth_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
