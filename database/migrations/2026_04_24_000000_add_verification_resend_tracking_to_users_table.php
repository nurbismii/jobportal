<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVerificationResendTrackingToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('verification_email_last_sent_at')
                ->nullable()
                ->after('email_verifikasi_token');
            $table->unsignedTinyInteger('verification_resend_count')
                ->default(0)
                ->after('verification_email_last_sent_at');
            $table->date('verification_resend_count_date')
                ->nullable()
                ->after('verification_resend_count');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'verification_email_last_sent_at',
                'verification_resend_count',
                'verification_resend_count_date',
            ]);
        });
    }
}
