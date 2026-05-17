<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVhirePkwtIntegrationTables extends Migration
{
    public function up()
    {
        Schema::create('vhire_contract_settings', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedSmallInteger('duration_value')->default(3);
            $table->string('duration_unit', 20)->default('month');
            $table->string('default_signing_method', 20)->default('electronic');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('vhire_onboarding_candidates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lamaran_id')->nullable()->unique();
            $table->unsignedBigInteger('biodata_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('vhire_candidate_id')->unique();
            $table->string('candidate_code')->unique();
            $table->string('no_ktp', 16)->index();
            $table->string('nama');
            $table->string('jabatan')->nullable();
            $table->date('tanggal_mulai_kerja')->nullable();
            $table->string('departemen')->nullable();
            $table->string('lokasi')->nullable();
            $table->string('recruitment_status')->default('proses_tanda_tangan_kontrak');
            $table->string('onboarding_status')->default('draft');
            $table->unsignedSmallInteger('contract_duration_value');
            $table->string('contract_duration_unit', 20)->default('month');
            $table->string('signing_method', 20)->default('electronic');
            $table->json('payload')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->string('sync_status')->default('draft')->index();
            $table->text('last_sync_error')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamp('last_sync_attempt_at')->nullable();
            $table->timestamps();
        });

        Schema::create('vhire_pkwt_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('onboarding_candidate_id')->nullable()->index();
            $table->string('hris_contract_id')->nullable()->unique();
            $table->string('vhire_candidate_id')->index();
            $table->string('candidate_code')->index();
            $table->string('no_ktp', 16)->index();
            $table->string('nama');
            $table->string('kode_kontrak')->nullable()->unique();
            $table->string('no_pkwt')->nullable()->unique();
            $table->string('jabatan')->nullable();
            $table->string('departemen')->nullable();
            $table->string('lokasi')->nullable();
            $table->date('tanggal_mulai_kontrak')->nullable();
            $table->date('tanggal_akhir_kontrak')->nullable();
            $table->unsignedSmallInteger('duration_value')->nullable();
            $table->string('duration_unit', 20)->nullable();
            $table->string('durasi_kontrak')->nullable();
            $table->decimal('gaji', 18, 2)->nullable();
            $table->string('status_tanda_tangan')->default('draft')->index();
            $table->string('signature_status')->default('draft')->index();
            $table->string('signing_method', 20)->default('electronic')->index();
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_by_source')->nullable();
            $table->boolean('visible_in_vhire')->default(true)->index();
            $table->string('hidden_reason')->nullable();
            $table->timestamp('hidden_at')->nullable();
            $table->string('employee_nik')->nullable()->index();
            $table->timestamp('activated_as_employee_at')->nullable();
            $table->string('manual_signed_file_path')->nullable();
            $table->string('manual_signed_file_disk')->nullable();
            $table->string('manual_signed_file_name')->nullable();
            $table->string('manual_signed_file_mime')->nullable();
            $table->unsignedBigInteger('manual_uploaded_by')->nullable();
            $table->timestamp('manual_uploaded_at')->nullable();
            $table->string('manual_verification_status')->nullable();
            $table->text('manual_note')->nullable();
            $table->string('contract_file_path')->nullable();
            $table->string('contract_file_disk')->nullable();
            $table->string('contract_file_name')->nullable();
            $table->string('contract_file_mime')->nullable();
            $table->longText('contract_content')->nullable();
            $table->json('source_payload')->nullable();
            $table->timestamp('last_imported_at')->nullable();
            $table->text('last_hris_sync_error')->nullable();
            $table->timestamps();
        });

        Schema::create('vhire_pkwt_contract_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id')->index();
            $table->string('event')->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('source')->default('system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('vhire_integration_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event')->index();
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('vhire_candidate_id')->nullable()->index();
            $table->string('candidate_code')->nullable()->index();
            $table->string('no_ktp_masked')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->string('source')->default('system')->index();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('vhire_integration_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('direction', 20)->index();
            $table->string('method', 10)->default('POST');
            $table->string('endpoint');
            $table->string('status')->default('pending')->index();
            $table->string('idempotency_key')->nullable()->index();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->json('payload')->nullable();
            $table->longText('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->boolean('retry_available')->default(true);
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vhire_integration_sync_logs');
        Schema::dropIfExists('vhire_integration_audit_logs');
        Schema::dropIfExists('vhire_pkwt_contract_histories');
        Schema::dropIfExists('vhire_pkwt_contracts');
        Schema::dropIfExists('vhire_onboarding_candidates');
        Schema::dropIfExists('vhire_contract_settings');
    }
}
