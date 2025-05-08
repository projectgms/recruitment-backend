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
        if (!Schema::hasTable('job_applications'))
        {
            Schema::create('job_applications', function (Blueprint $table) {
                $table->id();
                $table->string('bash_id')->unique();
                $table->integer('job_id');
                $table->integer('job_seeker_id');
                $table->integer('resume_id')->default(0);
                $table->text('resume')->nullable();
                $table->text('resume_json')->nullable();
                $table->text('cover_letter')->nullable();
                $table->string('status')->default('Applied');
                $table->timestamp('applied_at')->useCurrent();
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
        Schema::dropIfExists('job_applications');
    }
};
