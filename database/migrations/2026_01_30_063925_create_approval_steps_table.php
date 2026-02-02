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
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();

            // Reference
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            // Optional link ke template aslinya (untuk referensi)
            $table->foreignId('template_step_id')->nullable()->constrained('approval_template_steps')->nullOnDelete();

            // Step info
            $table->integer('sequence');
            $table->string('step_name');

            // Siapa yang assigned saat runtime?
            $table->foreignId('approver_id')->constrained('users')->restrictOnDelete();

            // Support fitur Delegasi (Penting untuk Enterprise)
            $table->foreignId('delegated_from_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('delegation_start_date')->nullable();
            $table->date('delegation_end_date')->nullable();

            // Status
            $table->string('status', 50)->default('pending'); // pending, approved, rejected, returned

            // Action metadata
            $table->timestamp('action_taken_at')->nullable();
            $table->foreignId('action_taken_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('comments')->nullable();

            $table->integer('sla_hours')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamps();
        });

        DB::statement("
            ALTER TABLE approval_steps
            ADD CONSTRAINT chk_approval_status
            CHECK (status IN ('pending',
                'approved',
                'rejected',
                'returned',
                'skipped'))
        ");

        Schema::table('approval_steps', function (Blueprint $table) {
            $table->index('document_id', 'idx_approval_steps_document');
            $table->index('status', 'idx_approval_steps_status');
        });

        /**
         * Database hanya akan membuat daftar index (daftar pencarian cepat) untuk data yang statusnya 'pending' saja. Data yang statusnya approved atau rejected tidak akan dimasukkan ke dalam index ini.
         */
        DB::statement('
            CREATE INDEX idx_approval_steps_approver
            ON approval_steps (approver_id, status)
            WHERE status = \'pending\'
        ');

        DB::statement('
            CREATE INDEX idx_approval_steps_due_at
            ON approval_steps (due_at)
            WHERE status = \'pending\' AND due_at IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_steps');
    }
};
