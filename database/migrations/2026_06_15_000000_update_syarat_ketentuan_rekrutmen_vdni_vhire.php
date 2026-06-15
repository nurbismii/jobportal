<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateSyaratKetentuanRekrutmenVdniVhire extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('syarat_ketentuan')) {
            Schema::create('syarat_ketentuan', function (Blueprint $table) {
                $table->id();
                $table->longText('syarat_ketentuan');
                $table->timestamps();
            });
        }

        if (Schema::hasTable('biodata')) {
            if (Schema::hasColumn('biodata', 'status_pernyataan')) {
                DB::statement('ALTER TABLE `biodata` MODIFY `status_pernyataan` LONGTEXT NULL');
            }

            if (! Schema::hasColumn('biodata', 'syarat_ketentuan_id')) {
                Schema::table('biodata', function (Blueprint $table) {
                    $column = $table->unsignedBigInteger('syarat_ketentuan_id')->nullable();

                    if (Schema::hasColumn('biodata', 'status_pernyataan')) {
                        $column->after('status_pernyataan');
                    }

                    $column->index('biodata_syarat_ketentuan_id_index');
                });
            }

            if (! Schema::hasColumn('biodata', 'status_pernyataan_disetujui_pada')) {
                Schema::table('biodata', function (Blueprint $table) {
                    $table->timestamp('status_pernyataan_disetujui_pada')
                        ->nullable()
                        ->after('syarat_ketentuan_id');
                });
            }
        }

        $htmlPath = database_path('data/syarat_ketentuan_rekrutmen_vdni_vhire_2026.html');

        if (! is_file($htmlPath)) {
            throw new RuntimeException('File syarat dan ketentuan baru tidak ditemukan: ' . $htmlPath);
        }

        $payload = [
            'syarat_ketentuan' => file_get_contents($htmlPath),
        ];

        if (Schema::hasColumn('syarat_ketentuan', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        if (DB::table('syarat_ketentuan')->where('id', 1)->exists()) {
            DB::table('syarat_ketentuan')->where('id', 1)->update($payload);

            return;
        }

        if (Schema::hasColumn('syarat_ketentuan', 'created_at')) {
            $payload['created_at'] = now();
        }

        DB::table('syarat_ketentuan')->insert(array_merge(['id' => 1], $payload));
    }

    public function down()
    {
        if (! Schema::hasTable('biodata')) {
            return;
        }

        Schema::table('biodata', function (Blueprint $table) {
            if (Schema::hasColumn('biodata', 'status_pernyataan_disetujui_pada')) {
                $table->dropColumn('status_pernyataan_disetujui_pada');
            }

            if (Schema::hasColumn('biodata', 'syarat_ketentuan_id')) {
                $table->dropIndex('biodata_syarat_ketentuan_id_index');
                $table->dropColumn('syarat_ketentuan_id');
            }
        });
    }
}
