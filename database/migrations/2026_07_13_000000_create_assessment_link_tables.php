<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssessmentLinkTables extends Migration
{
    public function up()
    {
        Schema::create('assessment_links', function (Blueprint $table) {
            $table->id();
            $table->string('assessment_type', 20)->index();
            $table->string('public_token', 64)->unique();
            $table->string('pin_hash');
            $table->json('form_schema');
            $table->unsignedBigInteger('created_by')->index();
            $table->timestamp('expires_at')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('deactivated_at')->nullable();
            $table->unsignedBigInteger('deactivated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('assessment_link_candidates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_link_id')->index();
            $table->unsignedBigInteger('lamaran_id')->index();
            $table->json('result_values')->nullable();
            $table->text('petugas_note')->nullable();
            $table->timestamp('last_submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['assessment_link_id', 'lamaran_id']);
        });

        Schema::create('assessment_result_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_link_candidate_id')->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('assessment_result_audits');
        Schema::dropIfExists('assessment_link_candidates');
        Schema::dropIfExists('assessment_links');
    }
}
