<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('document_id')->constrained()->cascadeOnDelete();

            // File info
            $table->string('filename');
            $table->string('original_filename');
            $table->string('file_path', 500);
            $table->string('mime_type', 100);
            $table->bigInteger('file_size');

            // Metadata
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();

            $table->timestamp('created_at');
        });

        // Add check constraint for file size (max 10MB)
        DB::statement("
            ALTER TABLE attachments
            ADD CONSTRAINT chk_file_size
            CHECK (file_size > 0 AND file_size <= 10485760)
        ");

        Schema::table('attachments', function (Blueprint $table) {
            $table->index('document_id', 'idx_attachments_document');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
