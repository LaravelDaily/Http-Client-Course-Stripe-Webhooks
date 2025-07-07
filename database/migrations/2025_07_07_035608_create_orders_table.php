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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_order_id')->unique();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('customer_email');
            $table->integer('amount'); // Amount in cents
            $table->string('currency', 3)->default('usd');
            $table->enum('status', ['pending', 'completed', 'payment_failed', 'canceled'])->default('pending');
            $table->integer('ticket_quantity')->default(1);
            $table->string('ticket_type')->default('general');
            $table->date('visit_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('payment_details')->nullable();
            $table->json('tickets')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
