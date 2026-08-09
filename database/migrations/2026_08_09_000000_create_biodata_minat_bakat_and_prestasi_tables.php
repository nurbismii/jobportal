<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateBiodataMinatBakatAndPrestasiTables extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('biodata', 'bakat')) {
            Schema::table('biodata', function (Blueprint $table) {
                $table->string('bakat')->nullable()->after('hobi');
            });
        }

        if (! Schema::hasTable('biodata_minat_bakat')) {
            $this->createMinatBakatTable();
        } else {
            $this->repairBiodataForeignKey('biodata_minat_bakat');
        }

        if (! Schema::hasTable('biodata_prestasi')) {
            $this->createPrestasiTable();
        } else {
            $this->repairBiodataForeignKey('biodata_prestasi');
        }
    }

    private function createMinatBakatTable(): void
    {
        Schema::create('biodata_minat_bakat', function (Blueprint $table) {
            $table->id();
            $this->addBiodataIdColumn($table);
            $table->string('tipe', 10);
            $table->string('kategori', 20);
            $table->string('nama', 100);
            $table->timestamps();

            $this->addBiodataForeignKey($table);
            $table->unique(
                ['biodata_id', 'tipe', 'kategori', 'nama'],
                'biodata_minat_bakat_unique'
            );
            $table->index(['tipe', 'kategori']);
        });
    }

    private function createPrestasiTable(): void
    {
        Schema::create('biodata_prestasi', function (Blueprint $table) {
            $table->id();
            $this->addBiodataIdColumn($table);
            $table->string('bidang', 30);
            $table->string('bidang_lainnya', 100)->nullable();
            $table->string('jenis_prestasi', 150);
            $table->string('peringkat', 100);
            $table->string('tingkat', 30);
            $table->char('periode', 7);
            $table->timestamps();

            $this->addBiodataForeignKey($table);
            $table->index(['bidang', 'tingkat']);
            $table->index('periode');
        });
    }

    private function addBiodataIdColumn(Blueprint $table): void
    {
        $definition = $this->biodataIdDefinition();

        if ($definition['bigint']) {
            $definition['unsigned']
                ? $table->unsignedBigInteger('biodata_id')
                : $table->bigInteger('biodata_id');

            return;
        }

        $definition['unsigned']
            ? $table->unsignedInteger('biodata_id')
            : $table->integer('biodata_id');
    }

    private function addBiodataForeignKey(Blueprint $table): void
    {
        $table->foreign('biodata_id')
            ->references('id')
            ->on('biodata')
            ->onDelete('cascade');
    }

    private function repairBiodataForeignKey(string $table): void
    {
        if ($this->hasBiodataForeignKey($table)) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            $definition = $this->biodataIdDefinition();
            DB::statement(sprintf(
                'ALTER TABLE `%s` MODIFY `biodata_id` %s NOT NULL',
                $table,
                $definition['sql']
            ));
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $this->addBiodataForeignKey($blueprint);
        });
    }

    private function hasBiodataForeignKey(string $table): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select("PRAGMA foreign_key_list(`{$table}`)"))
                ->contains(function ($foreignKey) {
                    return $foreignKey->table === 'biodata'
                        && $foreignKey->from === 'biodata_id'
                        && $foreignKey->to === 'id';
                });
        }

        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', 'biodata_id')
            ->where('REFERENCED_TABLE_NAME', 'biodata')
            ->where('REFERENCED_COLUMN_NAME', 'id')
            ->exists();
    }

    private function biodataIdDefinition(): array
    {
        if (DB::getDriverName() !== 'mysql') {
            return [
                'bigint' => true,
                'unsigned' => true,
                'sql' => 'BIGINT UNSIGNED',
            ];
        }

        $column = DB::selectOne("SHOW COLUMNS FROM `biodata` WHERE Field = 'id'");
        $type = strtolower((string) $column->Type);
        $bigint = strpos($type, 'bigint') !== false;
        $unsigned = strpos($type, 'unsigned') !== false;

        return [
            'bigint' => $bigint,
            'unsigned' => $unsigned,
            'sql' => ($bigint ? 'BIGINT' : 'INT') . ($unsigned ? ' UNSIGNED' : ''),
        ];
    }

    public function down()
    {
        Schema::dropIfExists('biodata_prestasi');
        Schema::dropIfExists('biodata_minat_bakat');

        if (Schema::hasColumn('biodata', 'bakat')) {
            Schema::table('biodata', function (Blueprint $table) {
                $table->dropColumn('bakat');
            });
        }
    }
}
