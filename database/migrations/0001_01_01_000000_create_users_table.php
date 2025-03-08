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
            $table->string('mobile');
            $table->string('role');
            $table->string('role_id');
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
            $table->string('oauth_provider');
            $table->integer('active');
            $table->string('status');
            $table->dateTime('last_login');
            $table->string('ip_address')->nullable();
            $table->timestamps();
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
