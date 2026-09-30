<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $orphans = DB::table('biodata')->whereNotExists(function ($query) {
            $query->selectRaw('1')->from('users')->whereColumn('users.id', 'biodata.user_id');
        })->count();

        if ($orphans > 0) {
            throw new RuntimeException("Ditemukan {$orphans} biodata orphan. Jalankan biodata:audit-orphans dan pulihkan relasi yang terverifikasi sebelum migration. Tidak ada data yang dihapus otomatis.");
        }

        Schema::table('biodata', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
        Schema::table('biodata', function (Blueprint $table) {
            $table->foreign('user_id', 'biodata_user_id_foreign')
                ->references('id')->on('users')
                ->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('biodata', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        // Keep BIGINT UNSIGNED: narrowing back to INT could lose newer user IDs.
    }
};
