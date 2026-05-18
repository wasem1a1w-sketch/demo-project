<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('cart_items', 'variant_combination_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropForeign(['variant_combination_id']);
                $table->dropColumn('variant_combination_id');
            });
        }

        if (Schema::hasColumn('order_items', 'variant_combination_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('variant_label');
                $table->dropForeign(['variant_combination_id']);
                $table->dropColumn('variant_combination_id');
            });
        }

        Schema::dropIfExists('product_variant_combination_options');
        Schema::dropIfExists('product_variant_combinations');
        Schema::dropIfExists('product_variant_options');
        Schema::dropIfExists('product_variant_groups');
    }

    public function down(): void
    {
        Schema::create('product_variant_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_variant_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('product_variant_groups')->cascadeOnDelete();
            $table->string('name');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_variant_combinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->nullable()->unique();
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('stock')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_variant_combination_options', function (Blueprint $table) {
            $table->foreignId('combination_id')->constrained('product_variant_combinations')->cascadeOnDelete();
            $table->foreignId('option_id')->constrained('product_variant_options')->cascadeOnDelete();
            $table->primary(['combination_id', 'option_id']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('variant_combination_id')->nullable()->constrained('product_variant_combinations')->nullOnDelete();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('variant_combination_id')->nullable()->constrained('product_variant_combinations')->nullOnDelete();
            $table->string('variant_label')->nullable();
        });
    }
};
