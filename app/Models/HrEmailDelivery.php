<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrEmailDelivery extends Model
{
    public const STATUS_LABELS = [
        'pending' => 'Menunggu antrean',
        'retrying' => 'Menunggu retry',
        'processing' => 'Sedang dikirim',
        'sent' => 'Terkirim',
        'failed' => 'Gagal',
        'skipped' => 'Dilewati',
    ];

    protected $guarded = [];

    protected $casts = ['sent_at' => 'datetime', 'attachments' => 'array'];
}
