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
        Schema::create('policies', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('policy_type', 50);

            $table->foreignId('document_type_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('roles')->cascadeOnDelete();

            $table->jsonb('rules');

            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // konflik rules → highest priority wins
            $table->timestamps();
        });

        DB::statement("
            ALTER TABLE policies
            ADD CONSTRAINT chk_policy_type
            CHECK (
                policy_type IN(
                    'quota_limit',
                    'amount_treshold',
                    'time_based',
                    'custom'
                )
            )
        ");

        Schema::table('policies', function (Blueprint $table) {
            $table->index(['document_type_id', 'is_active'], 'idx_policies_document_type');
            $table->index(['priority', 'is_active'], 'idx_policies_priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policies');
    }
};
