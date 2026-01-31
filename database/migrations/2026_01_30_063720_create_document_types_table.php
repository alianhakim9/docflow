<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();

            // Type definition
            $table->string('name')->unique();
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();

            // Form definition (dynamic fields)
            $table->jsonb('form_schema');

            // Settings
            $table->boolean('requires_attachment')->default(false);
            $table->integer('max_attachments')->default(5);
            $table->boolean('is_active')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
