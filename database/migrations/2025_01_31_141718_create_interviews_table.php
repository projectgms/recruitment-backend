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
        if (!Schema::hasTable('interviews'))
        {
            Schema::create('interviews', function (Blueprint $table) {
                $table->id();
                $table->integer('application_id');
                $table->integer('recruiter_id');
                $table->integer('company_id');
                $table->dateTime('interview_date');
                $table->string('interview_mode');
                $table->text('interview_link');
                $table->string('status');
                $table->text('feedback');
                $table->timestamps();
            });
       }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
