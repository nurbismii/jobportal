<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Verified;

class SetAkunStatusVerified
{
    /**
     * Handle the event.
     */
    public function handle(Verified $event)
    {
        $user = $event->user;
        $user->status_akun = 1;
        $user->save();
    }
}
