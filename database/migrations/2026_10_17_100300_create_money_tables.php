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
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('provider', 20);
            $table->string('reference', 64)->unique();
            $table->unsignedInteger('amount_kobo');
            $table->char('currency', 3)->default('NGN');
            $table->string('status', 20)->index();
            $table->string('channel', 30)->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->json('gateway_response')->nullable();
            $table->unsignedInteger('refunded_kobo')->default(0);
            $table->string('refund_reference')->nullable();
            $table->dateTime('refunded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('credit_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->integer('amount_kobo');
            $table->string('reason', 30);
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('created_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_ledger_entries');
        Schema::dropIfExists('payments');
    }
};
