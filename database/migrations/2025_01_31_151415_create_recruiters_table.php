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
        if (!Schema::hasTable('recruiters'))
        {
            Schema::create('recruiters', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');
                $table->integer('company_id');
                $table->string('role');
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
        Schema::dropIfExists('recruiters');
    }
};
