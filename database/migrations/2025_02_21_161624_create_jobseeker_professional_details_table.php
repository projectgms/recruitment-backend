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
                $table->longText('experience')->nullable();
                $table->longText('internship')->nullable();
                $table->longText('projects')->nullable();
                $table->text('summary')->nullable();
                $table->text('skills')->nullable();
                $table->text('soft_skills')->nullable();
                $table->text('achievement')->nullable();
                $table->text('awards')->nullable();
                $table->text('hobbies')->nullable();
                $table->text('extra_curricular')->nullable();

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
        Schema::dropIfExists('jobseeker_professional_details');
    }
};
