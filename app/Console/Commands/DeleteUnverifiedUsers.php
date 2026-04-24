<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Carbon\Carbon;

class DeleteUnverifiedUsers extends Command
{
    protected $signature = 'users:cleanup-unverified';
    protected $description = 'Delete users who did not verify their email within 1 hour of the latest verification email';

    public function handle()
    {
        $limit = Carbon::now()->subHours(1);

        $users = User::whereNull('email_verified_at')
            ->where('role', 'user')
            ->where(function ($query) use ($limit) {
                $query->where(function ($subQuery) use ($limit) {
                    $subQuery->whereNotNull('verification_email_last_sent_at')
                        ->where('verification_email_last_sent_at', '<=', $limit);
                })->orWhere(function ($subQuery) use ($limit) {
                    $subQuery->whereNull('verification_email_last_sent_at')
                        ->where('created_at', '<=', $limit);
                });
            })
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
