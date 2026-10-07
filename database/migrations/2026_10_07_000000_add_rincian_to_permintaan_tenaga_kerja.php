<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->withLegacyDates(function ($connection) {
            if ($connection->getDriverName() === 'mysql') {
                $column = $connection->selectOne("SHOW COLUMNS FROM `permintaan_tenaga_kerja` WHERE Field = 'updated_at'");
                if (($column->Default ?? null) === '0000-00-00 00:00:00') {
                    $connection->statement('ALTER TABLE `permintaan_tenaga_kerja` ALTER COLUMN `updated_at` SET DEFAULT CURRENT_TIMESTAMP');
                }
            }

            Schema::table('permintaan_tenaga_kerja', function (Blueprint $table) {
                $table->json('rincian')->nullable();
            });
        });
    }

    public function down(): void
    {
        $this->withLegacyDates(function () {
            Schema::table('permintaan_tenaga_kerja', function (Blueprint $table) {
                $table->dropColumn('rincian');
            });
        });
    }

    private function withLegacyDates(callable $change): void
    {
        $connection = DB::connection($this->getConnection());
        if ($connection->getDriverName() !== 'mysql') {
            $change($connection);
            return;
        }

        // Preserve legacy zero dates during DDL; keep strict mode and restore the session afterward.
        $originalMode = $connection->selectOne('SELECT @@SESSION.sql_mode AS sql_mode')->sql_mode;
        $migrationMode = implode(',', array_diff(explode(',', $originalMode), ['NO_ZERO_DATE', 'NO_ZERO_IN_DATE']));
        $connection->statement('SET SESSION sql_mode = ?', [$migrationMode]);
        try {
            $change($connection);
        } finally {
            $connection->statement('SET SESSION sql_mode = ?', [$originalMode]);
        }
    }
};
