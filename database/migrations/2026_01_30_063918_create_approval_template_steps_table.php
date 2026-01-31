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
        Schema::create('approval_template_steps', function (Blueprint $table) {
            $table->id();

            $table->foreignId('approval_template_id')->constrained()->cascadeOnDelete();

            // Step definition
            $table->integer('sequence'); // 1, 2, 3...
            $table->string('step_name');

            // Siapa yang harus approve? (Role based atau Specific User)
            $table->string('approver_type', 50); // 'role', 'specific_user', 'manager'
            $table->foreignId('approver_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignId(column: 'approver_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Parallelism
            $table->boolean('is_parallel')->default(false); // Bisa approve berbarengan?

            // SLA
            $table->integer('sla_hours')->nullable(); // Target waktu approval
            $table->timestamps();

            // Constraint: Urutan step dalam 1 template harus unik
            $table->unique(['approval_template_id', 'sequence'], 'uq_template_sequence');
        });

        DB::statement("
            ALTER TABLE approval_template_steps 
            ADD CONSTRAINT chk_approver_type
            CHECK (approver_type IN(
                'role',
                'specific_user',
                'dynamic'
            ))
        ");

        Schema::table('approval_template_steps', function (Blueprint $table) {
            $table->index('approval_template_id', 'idx_template_steps_template');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_template_steps');
    }
};
