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
                $table->longText('educations')->nullable();
                $table->longText('documents')->nullable();
                $table->longText('publications')->nullable();
                $table->longText('trainings')->nullable();
                $table->longText('certifications')->nullable();
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
        Schema::dropIfExists('jobseeker_education_details');
    }
};
