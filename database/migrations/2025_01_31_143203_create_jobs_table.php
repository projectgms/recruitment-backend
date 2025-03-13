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
        if (!Schema::hasTable('jobs'))
        {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->string('bash_id')->unique();
                $table->integer('user_id');
                $table->integer('comapny_id');
                $table->string('job_title');
                $table->text('job_description');
                $table->string('job_type');
                $table->json('location');
                $table->json('industry');
                $table->string('is_hot_job');
                $table->string('contact_email');
                $table->string('salary_range');
                $table->json('skills_required');
                $table->json('round')->nullable();
                $table->string('experience_required');
                $table->date('expiration_date');
                $table->time('expiration_time');
                $table->text('responsibilities');
                $table->string('status');
                $table->integer('active');
                $table->timestamps();
            });
       }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
