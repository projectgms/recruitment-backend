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
           if (!Schema::hasTable('role_permissions')) {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('bash_id')->unique();
            $table->integer('role_id');
            $table->integer('company_id')->nullable();
            $table->string('menu');
            $table->integer('view');
            $table->integer('add');
            $table->integer('edit');
            $table->integer('delete');
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
        Schema::dropIfExists('role_permissions');
    }
};
