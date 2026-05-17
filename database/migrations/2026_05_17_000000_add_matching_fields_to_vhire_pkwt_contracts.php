<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMatchingFieldsToVhirePkwtContracts extends Migration
{
    public function up()
    {
        Schema::table('vhire_pkwt_contracts', function (Blueprint $table) {
            $table->string('match_status', 30)->default('pending_match')->index()->after('onboarding_candidate_id');
            $table->unsignedBigInteger('matched_biodata_id')->nullable()->index()->after('match_status');
            $table->unsignedBigInteger('matched_user_id')->nullable()->index()->after('matched_biodata_id');
            $table->unsignedBigInteger('matched_lamaran_id')->nullable()->index()->after('matched_user_id');
            $table->timestamp('matched_at')->nullable()->after('matched_lamaran_id');
            $table->unsignedBigInteger('matched_by')->nullable()->after('matched_at');
        });
    }

    public function down()
    {
        Schema::table('vhire_pkwt_contracts', function (Blueprint $table) {
            $table->dropColumn([
                'match_status',
                'matched_biodata_id',
                'matched_user_id',
                'matched_lamaran_id',
                'matched_at',
                'matched_by',
            ]);
        });
    }
}
