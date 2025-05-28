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
          if (!Schema::hasTable('company_events')) 
         {
            Schema::create('company_events', function (Blueprint $table) {
                $table->id();
                $table->string('bash_id')->unique();
                $table->integer('company_id');
                $table->string('title');
                $table->text('description');
                $table->longText('event_images');
                $table->integer('active')->default(1);
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_events');
    }
};
