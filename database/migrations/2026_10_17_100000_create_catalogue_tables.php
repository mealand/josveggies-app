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
        Schema::create('hubs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address');
            $table->text('directions')->nullable();
            $table->string('opening_note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('dispatch_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('areas')->nullable();
            $table->unsignedInteger('fee_kobo');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('pack_units', function (Blueprint $table) {
            $table->id();
            $table->string('traditional_name');
            $table->string('container');
            $table->unsignedInteger('min_weight_g')->nullable();
            $table->unsignedInteger('max_weight_g')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category', 40)->index();
            $table->foreignId('pack_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('reference_price_kobo');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('box_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('price_kobo');
            $table->string('photo_path')->nullable();
            $table->string('accent', 20)->default('leaf');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('box_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('box_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unique(['box_template_id', 'item_id']);
        });

        Schema::create('add_ons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedInteger('price_kobo');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('add_ons');
        Schema::dropIfExists('box_template_items');
        Schema::dropIfExists('box_templates');
        Schema::dropIfExists('items');
        Schema::dropIfExists('pack_units');
        Schema::dropIfExists('dispatch_zones');
        Schema::dropIfExists('hubs');
    }
};
