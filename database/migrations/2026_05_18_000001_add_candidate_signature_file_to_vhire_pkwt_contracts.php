<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCandidateSignatureFileToVhirePkwtContracts extends Migration
{
    public function up()
    {
        Schema::table('vhire_pkwt_contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('vhire_pkwt_contracts', 'signature_file_disk')) {
                $table->string('signature_file_disk')->nullable()->after('signed_by_source');
            }

            if (!Schema::hasColumn('vhire_pkwt_contracts', 'signature_file_path')) {
                $table->string('signature_file_path', 500)->nullable()->after('signature_file_disk');
            }

            if (!Schema::hasColumn('vhire_pkwt_contracts', 'signature_file_mime')) {
                $table->string('signature_file_mime', 100)->nullable()->after('signature_file_path');
            }

            if (!Schema::hasColumn('vhire_pkwt_contracts', 'signature_file_hash')) {
                $table->string('signature_file_hash', 128)->nullable()->after('signature_file_mime');
            }
        });
    }

    public function down()
    {
        Schema::table('vhire_pkwt_contracts', function (Blueprint $table) {
            $columns = [
                'signature_file_hash',
                'signature_file_mime',
                'signature_file_path',
                'signature_file_disk',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('vhire_pkwt_contracts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
