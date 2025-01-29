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
        if (!Schema::hasTable('oem_users')) {
        Schema::create('oem_users', function (Blueprint $table) {
            $table->id();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('mobile');
            $table->date('dob');
            $table->integer('role_id');
            $table->string('role');
            $table->integer('active');
            $table->string('status');
            $table->rememberToken();
            $table->dateTime('last_login_at');
            $table->integer('aggregater_id');
            $table->dateTime('otp_expire_time')->nullable();
            $table->integer('otp');
            $table->integer('created_by');
            $table->integer('created_role');
            $table->integer('updated_by');
            $table->timestamps();
        });
    }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oem_users');
    }
};
