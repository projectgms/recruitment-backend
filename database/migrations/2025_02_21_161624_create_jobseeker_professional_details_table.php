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
                $table->json('experience')->nullable();
                $table->json('internship')->nullable();
                $table->json('projects')->nullable();
                $table->text('summery')->nullable();
                $table->text('skills')->nullable();
                $table->text('achievement')->nullable();
                $table->text('awards')->nullable();
                $table->text('hobbies')->nullable();

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
