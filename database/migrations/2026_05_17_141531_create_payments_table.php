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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('provider')->default('stripe')->comment('stripe, paypal, razorpay');
            $table->string('provider_transaction_id')->nullable()->index();
            $table->string('provider_session_id')->nullable()->index();
            $table->json('provider_response')->nullable()->comment('Raw response from payment provider');
            $table->string('status')->default('pending')->index()->comment('pending, paid, failed, refunded, expired');
            $table->integer('attempts')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
