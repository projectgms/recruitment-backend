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
           if (!Schema::hasTable('candidate_skill_tests')) {
        Schema::create('candidate_skill_tests', function (Blueprint $table) {
            $table->id();
            $table->string('bash_id')->unique();
            $table->integer('jobseeker_id');
            $table->string('skill');
            $table->integer('score');
            $table->integer('total');
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
        Schema::dropIfExists('candidate_skill_tests');
    }
};
