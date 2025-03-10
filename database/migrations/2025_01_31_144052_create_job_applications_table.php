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
                $table->integer('job_id');
                $table->integer('job_seeker_id');
                $table->text('resume');
                $table->text('cover_letter');
                $table->string('status');
                $table->timestamps();
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
