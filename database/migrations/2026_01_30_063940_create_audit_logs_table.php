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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();


            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();

            $table->string('action', 100);
            // submit, approve, reject, return, delegate, update, delete
            $table->string('entity_type', 100); // Document, ApprovalStep, Policy
            $table->bigInteger('entity_id');

            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->jsonb('metadata')->nullable(); // ip, user_agent, request_id

            $table->timestamp('created_at');
        });

        DB::statement("
            ALTER TABLE audit_logs
            ADD CONSTRAINT chk_entity_type
            CHECK (entity_type IN (
                'Document',
                'ApprovalStep',
                'User',
                'Policy'
            ))
        ");

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['entity_type', 'entity_id'], 'idx_audit_logs_entity');
            $table->index('user_id', 'idx_audit_logs_user');
            $table->index('action', 'idx_audit_logs_action');
            $table->index('created_at', 'idx_audit_logs_created_at');
        });

        DB::statement('
            CREATE INDEX idx_audit_logs_document_history
            ON audit_logs (entity_type, entity_id, created_at DESC)
            WHERE entity_type = \'Document\'
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
