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
        if (!Schema::hasTable('recruiter_prepare_jobs')) 
         {
            Schema::create('recruiter_prepare_jobs', function (Blueprint $table) {
                $table->id();
                 $table->string('bash_id')->unique();
                $table->integer('job_application_id');
                $table->integer('job_id');
               
                $table->integer('company_id');
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
        Schema::dropIfExists('recruiter_prepare_jobs');
    }
};
