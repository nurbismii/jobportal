<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessLamaranMasterJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public $ids, $status, $tanggal, $jam, $tempat, $pesan, $blast;

    public function __construct($ids, $status, $tanggal, $jam, $tempat, $pesan, $blast)
    {
        $this->ids = $ids;
        $this->status = $status;
        $this->tanggal = $tanggal;
        $this->jam = $jam;
        $this->tempat = $tempat;
        $this->pesan = $pesan;
        $this->blast = $blast;
    }

    public function handle()
    {
        collect($this->ids)
            ->chunk(300)
            ->each(function ($chunk) {
                ProcessLamaranEmailJob::dispatch(
                    $chunk->toArray(),
                    $this->status,
                    $this->tanggal,
                    $this->jam,
                    $this->tempat,
                    $this->pesan,
                    $this->blast
                );
            });
    }
}
