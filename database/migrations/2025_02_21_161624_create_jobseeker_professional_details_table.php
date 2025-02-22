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
        if (!Schema::hasTable('jobseeker_professional_details')) {
            Schema::create('jobseeker_professional_details', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');
                $table->string('bash_id')->unique();
                $table->json('experience');
                $table->json('internship');
                $table->json('projects');
                $table->text('summery');
                $table->text('skills');
                $table->text('achievement');
                $table->text('awards');
                $table->text('hobbies');

                $table->timestamps();
            });
         }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobseeker_professional_details');
    }
};
