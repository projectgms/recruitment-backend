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
        if (!Schema::hasTable('users')) {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('bash_id')->unique();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->string('mobile')->nullable();
            $table->string('role')->nullable();
            $table->string('role_id')->default(0);
            $table->string('company_id')->nullable();
            $table->text('profile_picture')->nullable();
            $table->text('location')->nullable();
            $table->string('gender')->nullable();
            $table->date('dob')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('medical_history')->nullable();
            $table->string('disability')->nullable();
            $table->string('language_known')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('oauth_provider')->nullable();
            $table->integer('open_to_work')->default(0);
            $table->integer('active')->default(1);
            $table->string('status')->default('active');
            $table->dateTime('last_login')->nullable();
            $table->string('ip_address')->nullable();
            $table->integer('added_by')->nullable();
             $table->timestamp('created_at')->useCurrent();
             $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }
 
    if (!Schema::hasTable('sessions')) {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
