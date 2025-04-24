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
            if (!Schema::hasTable('ai_analysis_resumes')) {
        Schema::create('ai_analysis_resumes', function (Blueprint $table) {
            $table->id();
            $table->string('bash_id')->unique();
            $table->integer('jobseeker_id');
            $table->integer('resume_generate_id');
            $table->integer('ai_analysis');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
            }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_analysis_resumes');
    }
};
