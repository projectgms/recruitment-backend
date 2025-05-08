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
        if (!Schema::hasTable('jobs'))
        {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->string('bash_id')->unique();
                $table->integer('user_id');
                $table->integer('comapny_id');
                $table->string('job_title');
                $table->text('job_description');
                $table->string('job_type');
                $table->longText('location')->nullable();
                $table->longText('industry');
                $table->string('is_hot_job');
                $table->string('contact_email');
                $table->string('salary_range');
                $table->longText('skills_required');
                $table->longText('round')->nullable();
                $table->string('experience_required');
                $table->date('expiration_date');
                $table->time('expiration_time');
                $table->text('responsibilities');
                $table->string('is_pin')->default("No");
                $table->string('status');
                $table->integer('active');
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
        Schema::dropIfExists('jobs');
    }
};
