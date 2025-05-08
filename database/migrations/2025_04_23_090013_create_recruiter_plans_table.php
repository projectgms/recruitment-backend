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
        if (!Schema::hasTable('recruiter_plans')) {
        Schema::create('recruiter_plans', function (Blueprint $table) {
            $table->id();
            $table->string('bash_id')->unique();
            $table->string('plan_name');
            $table->string('plan_type');
            $table->string('amount');
            $table->longText('features');
            $table->string('status')->nullable();
            $table->integer('active')->default(1);
            $table->timestamps();
        });
    }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruiter_plans');
    }
};
