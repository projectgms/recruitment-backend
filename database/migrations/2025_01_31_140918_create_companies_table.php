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
                $table->string('website')->nullable();
                $table->string('industry')->nullable();
                $table->string('company_size')->nullable();
                $table->text('company_description')->nullable();
                $table->longtext('locations')->nullable();
                $table->longtext('company_logo')->nullable();
                $table->longtext('social_profiles')->nullable();
                 $table->string('facebook_url')->nullable();
                $table->string('instagram_url')->nullable();
                $table->string('linkedin_url')->nullable();
                $table->string('twitter_url')->nullable();
                $table->string('status')->nullable();
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
        Schema::dropIfExists('companies');
    }
};
