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
        Schema::create('generate_resumes', function (Blueprint $table) {
            $table->id();
            $table->string('bash_id')->unique();
            $table->integer('user_id');
            $table->string('resume_name');
            $table->string('resume');
            $table->json('resume_json');
            $table->integer('active');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generate_resumes');
    }
};
