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
                $table->integer('user_id');
                $table->integer('comapny_id');
                $table->string('job_title');
                $table->text('job_description');
                $table->string('job_type');
                $table->string('location');
                $table->string('salary_range');
                $table->text('skills_required');
                $table->string('experience_required');
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
