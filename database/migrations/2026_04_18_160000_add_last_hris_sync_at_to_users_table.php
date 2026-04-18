<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLastHrisSyncAtToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_hris_sync_at')
                ->nullable()
                ->after('employment_lock_active')
                ->index();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['last_hris_sync_at']);
            $table->dropColumn('last_hris_sync_at');
        });
    }
}
