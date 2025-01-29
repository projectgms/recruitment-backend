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
        if (!Schema::hasTable('database_settings')) {
        Schema::create('database_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('oem_id');
            $table->string('bash_id');
            $table->string('db_name');
            $table->string('db_username');
            $table->string('db_password')->nullable();
            $table->string('db_host');
            $table->integer('active');
            $table->string('status');
            $table->integer('created_by');
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
        Schema::dropIfExists('database_settings');
    }
};
