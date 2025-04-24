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
        if (!Schema::hasTable('job_post_notification_status')) {
        Schema::create('job_post_notification_status', function (Blueprint $table) {
            $table->id();
            $table->string('bash_id')->unique();
            $table->integer('jobseeker_id');
            $table->integer('job_post_notification_id');
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
        Schema::dropIfExists('job_post_notification_status');
    }
};
