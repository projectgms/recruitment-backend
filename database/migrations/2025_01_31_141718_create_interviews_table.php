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
        if (!Schema::hasTable('interviews'))
        {
            Schema::create('interviews', function (Blueprint $table) {
               $table->id();
                $table->string('bash_id')->unique();
                $table->integer('job_id');
                $table->integer('job_application_id');
                $table->integer('jobseeker_id');
                $table->integer('recruiter_id');
                $table->integer('company_id');
                  $table->integer('round_id');
                $table->string('score');
                $table->string('total');
                $table->dateTime('interview_date')->nullable();
                $table->string('interview_mode');
                $table->text('interview_link');
                $table->string('status');
                $table->text('feedback');
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
        Schema::dropIfExists('interviews');
    }
};
