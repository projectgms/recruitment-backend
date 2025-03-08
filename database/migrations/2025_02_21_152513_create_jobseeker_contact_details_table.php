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
                $table->string('course')->nullable();
                $table->string('primary_specialization')->nullable();
                $table->integer('total_year_exp');
                $table->integer('total_month_exp');
                $table->string('dream_company')->nullable();
                $table->string('secondary_mobile')->nullable();
                $table->string('secondary_email')->nullable();
                $table->string('linkedin_url')->nullable();
                $table->string('github_url')->nullable();
                $table->timestamps();
              
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
