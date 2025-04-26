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
        if (!Schema::hasTable('recruiter_subscriptions')) {
        Schema::create('recruiter_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('bash_id')->unique();
            $table->integer('company_id');
            $table->integer('plan_id');
            $table->string('plan_name');
            $table->integer('amount');
            $table->string('plan_type');
            $table->json('features');
            $table->date('plan_purchase_date');
            $table->date('plan_expiry_date');
            $table->string('rayzorpay_order_id');
            $table->string('rayzorpay_payment_id');
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
        Schema::dropIfExists('recruiter_subscriptions');
    }
};
