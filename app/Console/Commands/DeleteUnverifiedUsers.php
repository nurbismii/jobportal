<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DeleteUnverifiedUsers extends Command
{
    protected $signature = 'users:cleanup-unverified';
    protected $description = 'Delete users who did not verify their email within 1 hour of the latest verification email';

    public function handle()
    {
        $limit = Carbon::now()->subHours(1);

        $usersQuery = User::whereNull('email_verified_at')
            ->whereDoesntHave('biodata')
            ->where('role', 'user');

        if (User::supportsVerificationResendTracking()) {
            $usersQuery->where(function ($query) use ($limit) {
                $query->where(function ($subQuery) use ($limit) {
                    $subQuery->whereNotNull('verification_email_last_sent_at')
                        ->where('verification_email_last_sent_at', '<=', $limit);
                })->orWhere(function ($subQuery) use ($limit) {
                    $subQuery->whereNull('verification_email_last_sent_at')
                        ->where('created_at', '<=', $limit);
                });
            });
        } else {
            $usersQuery->where('updated_at', '<=', $limit);
        }

        $count = 0;
        $usersQuery->select('users.id')->chunkById(100, function ($users) use ($usersQuery, &$count) {
            foreach ($users as $candidate) {
                $count += DB::transaction(function () use ($candidate, $usersQuery) {
                    $user = User::whereKey($candidate->id)->lockForUpdate()->first();
                    if (! $user || ! (clone $usersQuery)->whereKey($user->id)->exists()) {
                        return 0;
                    }

                    return $user->delete() ? 1 : 0;
                });
            }
        });

        $this->info($count . ' unverified users deleted.');
    }
}
