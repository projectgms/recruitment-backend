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
        if (!Schema::hasTable('companies'))
        {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->string('bash_id')->unique();
                $table->integer('user_id');
                $table->string('name');
                $table->string('website');
                $table->string('industry');
                $table->string('company_size');
                $table->text('company_description');
                $table->longtext('locations')->nullable();
                $table->longtext('company_logo')->nullable();
                $table->longtext('social_profiles')->nullable();
                $table->string('status');
                $table->integer('active');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
