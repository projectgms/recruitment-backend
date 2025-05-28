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
         if (!Schema::hasTable('superadmin_roles')) {
        Schema::create('superadmin_roles', function (Blueprint $table) {
            $table->id();
            $table->string('bash_id')->unique();
            $table->string('role');
            $table->text('role_desc')->nullable();
            $table->integer('parent_id');
            $table->integer('active');
            $table->string('status')->nullable();
            $table->integer('added_by')->nullable();
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
        Schema::dropIfExists('superadmin_roles');
    }
};
