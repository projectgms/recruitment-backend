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
          if (!Schema::hasTable('generate_resumes')) {
        Schema::create('generate_resumes', function (Blueprint $table) {
            $table->id();
            $table->string('bash_id')->unique();
            $table->integer('user_id');
            $table->integer('job_id')->default(0);
            $table->string('resume_name');
            $table->string('resume')->nullable();
            $table->longText('resume_json');
              $table->string('is_ai_generated')->default('false');
            $table->integer('active')->default(1);
            $table->string('status');
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
        Schema::dropIfExists('generate_resumes');
    }
};
