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
        Schema::create('runs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('state', 20)->default('draft')->index();
            $table->foreignId('hub_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('slot_capacity');
            $table->unsignedSmallInteger('slots_confirmed')->default(0);
            $table->unsignedSmallInteger('slots_held')->default(0);
            $table->unsignedSmallInteger('break_even_boxes')->nullable();
            $table->dateTime('opens_at');
            $table->dateTime('closes_at');
            $table->date('market_day');
            $table->dateTime('delivery_starts_at');
            $table->dateTime('closed_at')->nullable();
            $table->dateTime('purchased_at')->nullable();
            $table->dateTime('packed_at')->nullable();
            $table->dateTime('departed_at')->nullable();
            $table->dateTime('arrived_at')->nullable();
            $table->dateTime('pickup_ends_at')->nullable();
            $table->dateTime('morning_reminder_sent_at')->nullable();
            $table->dateTime('final_reminder_sent_at')->nullable();
            $table->dateTime('waitlist_notified_at')->nullable();
            $table->dateTime('reconciled_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('box_template_run', function (Blueprint $table) {
            $table->foreignId('run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('box_template_id')->constrained()->cascadeOnDelete();
            $table->primary(['run_id', 'box_template_id']);
        });

        Schema::create('add_on_run', function (Blueprint $table) {
            $table->foreignId('run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('add_on_id')->constrained()->cascadeOnDelete();
            $table->primary(['run_id', 'add_on_id']);
        });

        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('run_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('notified_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'run_id']);
        });

        Schema::create('run_cost_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained()->cascadeOnDelete();
            $table->string('category', 20);
            $table->unsignedInteger('amount_kobo');
            $table->string('description');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('run_cost_entries');
        Schema::dropIfExists('waitlist_entries');
        Schema::dropIfExists('add_on_run');
        Schema::dropIfExists('box_template_run');
        Schema::dropIfExists('runs');
    }
};
