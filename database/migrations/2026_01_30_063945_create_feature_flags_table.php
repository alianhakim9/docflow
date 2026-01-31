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
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100)->unique();
            $table->boolean('is_enabled')->default(true);
            $table->integer('rollout_percentage')->default(100);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::table('feature_flags', function (Blueprint $table) {
            $table->index('name', 'idx_feature_flags_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
    }
};
