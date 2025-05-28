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
           if (!Schema::hasTable('skill_ass_questions')) {
        Schema::create('skill_ass_questions', function (Blueprint $table) {
            $table->id();
            
            $table->string('skill');
            $table->string('skill_level');
            $table->text('question');
            $table->string('option1');
            $table->string('option2');
            $table->string('option3');
            $table->string('option4');
            $table->string('correct_answer');
             $table->integer('marks')->default(1);
                $table->integer('company_id')->default(0);
                  $table->integer('job_id')->default(0);
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
        Schema::dropIfExists('skill_ass_questions');
    }
};
