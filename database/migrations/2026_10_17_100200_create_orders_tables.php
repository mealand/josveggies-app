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
            $table->ulid('ulid')->unique();
            $table->string('number', 20)->unique();
            $table->foreignId('run_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('state', 20)->index();
            $table->string('fulfilment', 10);
            $table->foreignId('dispatch_zone_id')->nullable()->constrained()->restrictOnDelete();
            $table->text('delivery_address')->nullable();
            $table->text('contact_phone');
            $table->unsignedSmallInteger('slots');
            $table->unsignedInteger('subtotal_kobo');
            $table->unsignedInteger('dispatch_fee_kobo')->default(0);
            $table->unsignedInteger('credit_applied_kobo')->default(0);
            $table->unsignedInteger('total_kobo');
            $table->json('accepted_policies');
            $table->dateTime('policies_accepted_at');
            $table->dateTime('hold_expires_at')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->string('pickup_code_hash', 64)->nullable();
            $table->text('pickup_code')->nullable();
            $table->dateTime('collected_at')->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('forfeited_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['run_id', 'state']);
            $table->unique(['run_id', 'pickup_code_hash']);
        });

        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10);
            $table->foreignId('box_template_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('add_on_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('unit_price_kobo');
            $table->unsignedInteger('line_total_kobo');
            $table->json('contents')->nullable();
            $table->dateTime('unavailable_at')->nullable();
            $table->timestamps();
        });

        Schema::create('substitutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_line_id')->constrained()->cascadeOnDelete();
            $table->foreignId('original_item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('substitute_item_id')->nullable()->constrained('items')->restrictOnDelete();
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('original_value_kobo');
            $table->unsignedInteger('substitute_value_kobo')->nullable();
            $table->unsignedInteger('credit_kobo')->default(0);
            $table->uuid('batch_id')->nullable()->index();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('notified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('substitutions');
        Schema::dropIfExists('order_lines');
        Schema::dropIfExists('orders');
    }
};
