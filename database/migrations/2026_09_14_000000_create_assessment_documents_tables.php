<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assessment_document_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_link_id')->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->timestamps();
            $table->unique(['assessment_link_id', 'name']);
        });
        Schema::create('assessment_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_link_id')->constrained()->restrictOnDelete();
            $table->foreignId('assessment_document_folder_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('category', 30);
            $table->string('original_name');
            $table->string('path')->unique();
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_documents');
        Schema::dropIfExists('assessment_document_folders');
    }
};
