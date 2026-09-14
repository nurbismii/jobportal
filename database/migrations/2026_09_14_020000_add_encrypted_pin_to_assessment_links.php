<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assessment_links', function (Blueprint $table) {
            $table->text('pin_encrypted')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('assessment_links', function (Blueprint $table) {
            $table->dropColumn('pin_encrypted');
        });
    }
};
