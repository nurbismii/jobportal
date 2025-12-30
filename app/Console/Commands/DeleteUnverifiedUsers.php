<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Carbon\Carbon;

class DeleteUnverifiedUsers extends Command
{
    protected $signature = 'users:cleanup-unverified';
    protected $description = 'Delete users who did not verify their email within 1 hours';

    public function handle()
    {
        $limit = Carbon::now()->subHours(1);

        $users = User::whereNull('email_verified_at')
            ->where('created_at', '<=', $limit)
            ->where('role', 'user')
            ->get();

        $count = $users->count();

        if ($count > 0) {
            foreach ($users as $user) {
                $user->delete();
            }
        }

        $this->info($count . ' unverified users deleted.');
    }
}
