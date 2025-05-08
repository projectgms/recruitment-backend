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
        if (!Schema::hasTable('job_application_notifications')) {
        Schema::create('job_application_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('bash_id')->unique();
            $table->integer('job_id');
            $table->integer('job_application_id');
            $table->integer('company_id');
            $table->integer('jobseeker_id');
            $table->string('type');
            $table->text('message');
            $table->integer('is_read');
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
        Schema::dropIfExists('job_application_notifications');
    }
};
