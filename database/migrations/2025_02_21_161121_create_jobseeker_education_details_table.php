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
        if (!Schema::hasTable('jobseeker_education_details')) {
            Schema::create('jobseeker_education_details', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');
                $table->string('bash_id')->unique();
                $table->json('educations');
                $table->json('documents');
                $table->json('publications');
                $table->json('trainings');
                $table->json('certifications');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobseeker_education_details');
    }
};
