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
        Schema::create('approval_templates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('document_type_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            // Logic: Menentukan template mana yang dipakai (misal: Amount > 5jt pakai template A)
            $table->jsonb('condition_rules')->nullable();

            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        // Partial unique constraint: only 1 default per document type
        DB::statement('
            CREATE UNIQUE INDEX idx_approval_templates_default
            ON approval_templates (document_type_id, is_default)
            WHERE is_default = true
        ');

        // Regular indexes
        Schema::table('approval_templates', function (Blueprint $table) {
            $table->index('document_type_id', 'idx_approval_templates_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_templates');
    }
};
