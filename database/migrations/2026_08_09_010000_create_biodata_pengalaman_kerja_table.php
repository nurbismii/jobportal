<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateBiodataPengalamanKerjaTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('biodata_pengalaman_kerja')) {
            return;
        }

        Schema::create('biodata_pengalaman_kerja', function (Blueprint $table) {
            $table->id();
            $this->addBiodataIdColumn($table);
            $table->string('nama_perusahaan', 150);
            $table->string('posisi', 150);
            $table->char('tanggal_mulai', 7);
            $table->char('tanggal_selesai', 7)->nullable();
            $table->boolean('masih_bekerja')->default(false);
            $table->unsignedTinyInteger('urutan');
            $table->timestamps();

            $table->foreign('biodata_id')
                ->references('id')
                ->on('biodata')
                ->onDelete('cascade');
            $table->unique(['biodata_id', 'urutan'], 'biodata_pengalaman_kerja_urutan_unique');
            $table->index(['biodata_id', 'tanggal_selesai', 'tanggal_mulai'], 'biodata_pengalaman_kerja_periode_index');
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

    private function biodataIdDefinition(): array
    {
        if (DB::getDriverName() !== 'mysql') {
            return ['bigint' => true, 'unsigned' => true];
        }

        $column = DB::selectOne("SHOW COLUMNS FROM `biodata` WHERE Field = 'id'");
        $type = strtolower((string) $column->Type);

        return [
            'bigint' => strpos($type, 'bigint') !== false,
            'unsigned' => strpos($type, 'unsigned') !== false,
        ];
    }

    public function down()
    {
        Schema::dropIfExists('biodata_pengalaman_kerja');
    }
}
