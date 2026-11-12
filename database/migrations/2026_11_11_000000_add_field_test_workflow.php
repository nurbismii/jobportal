<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->string('field_test_pin_hash')->nullable());
        Schema::table('assessment_links', fn (Blueprint $table) => $table->json('field_test_config')->nullable());
        Schema::table('assessment_link_candidates', function (Blueprint $table) {
            $table->unsignedInteger('field_test_revision')->default(1);
            $table->unsignedInteger('field_test_version')->default(1);
            $table->json('field_test_signatures')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('assessment_link_candidates', fn (Blueprint $table) => $table->dropColumn(['field_test_revision', 'field_test_version', 'field_test_signatures']));
        Schema::table('assessment_links', fn (Blueprint $table) => $table->dropColumn('field_test_config'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('field_test_pin_hash'));
    }
};
