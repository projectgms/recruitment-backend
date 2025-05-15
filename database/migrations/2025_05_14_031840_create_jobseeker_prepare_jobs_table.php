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
         if (!Schema::hasTable('jobseeker_prepare_jobs')) 
         {
            Schema::create('jobseeker_prepare_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('bash_id')->unique();
                $table->integer('job_application_id')->default(0);
                $table->integer('generate_resume_id')->default(0);
                $table->string('title');
                $table->integer('jobseeker_id');
                $table->longText('qa_output');
                $table->integer('active')->default(1);
                
                $table->timestamps();
            });
         }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobseeker_prepare_jobs');
    }
};
