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
          if (!Schema::hasTable('candidate_reviews')) 
         {
                Schema::create('candidate_reviews', function (Blueprint $table) {
                    $table->id();
                    $table->string('bash_id')->unique();
                    $table->integer('jobseeker_id');
                    $table->string('review');
                    $table->string('rating');
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
        Schema::dropIfExists('candidate_reviews');
    }
};
