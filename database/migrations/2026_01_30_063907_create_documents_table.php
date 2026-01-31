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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();

            // Reference
            $table->string('document_number', 100)->unique();
            $table->foreignId('document_type_id')->constrained()->restrictOnDelete();

            // Ownership
            $table->foreignId('submitter_id')->constrained('users')->restrictOnDelete();

            // Data
            $table->string('title', 500);
            $table->jsonb('data'); // JSONB: Menyimpan isi form (flexible fields)

            // Status State Machine
            $table->string('status', 50)->default('draft');

            // Metadata
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement("
            ALTER TABLE documents
            ADD CONSTRAINT chk_documents_status
            CHECK (status IN (
                'draft',
                'pending',
                'approved',
                'rejected',
                'returned',
                'cancelled',
                'completed'
            ))
        ");

        Schema::table('documents', function (Blueprint $table) {
            $table->index(['submitter_id', 'deleted_at'], 'idx_documents_submitter');
            $table->index(['status', 'deleted_at'], 'idx_documents_status');
            $table->index(['document_type_id', 'deleted_at'], 'idx_documents_type');
            $table->index(['submitted_at', 'deleted_at'], 'idx_documents_submitted_at');

            // Composite index for "my pending documents"
            $table->index(['submitter_id', 'status', 'deleted_at'], 'idx_documents_submitter_status');
        });

        // GIN index for JSONB (PostgreSQL specific)
        DB::statement('CREATE INDEX idx_documents_data_gin ON documents USING GIN  (data)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
