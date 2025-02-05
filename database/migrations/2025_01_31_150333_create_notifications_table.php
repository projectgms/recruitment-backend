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
        if (!Schema::hasTable('notifications'))
        {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');
                $table->text('message');
                $table->integer('is_read');
                $table->timestamps();
            }); 
       }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
