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
        if (!Schema::hasTable('job_seeker_contact_details')) {
            Schema::create('job_seeker_contact_details', function (Blueprint $table) {
                $table->id();
                $table->string('bash_id')->unique();
                $table->integer('user_id');
                $table->string('country')->nullable();
                $table->string('state')->nullable();
                $table->string('city')->nullable();
                $table->string('zipcode')->nullable();
                $table->string('course');
                $table->string('primary_specialization');
                $table->string('dream_company');
                $table->string('secondary_mobile');
                $table->string('secondary_email');
                $table->string('linkedin_url');
                $table->string('github_url');
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_seeker_contact_details');
      
    }
};
