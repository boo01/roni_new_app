<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name_ka');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_filterable')->default(true);
            $table->timestamps();
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->string('value_ka');
            $table->string('slug');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['attribute_id', 'slug']);
            $table->index(['attribute_id', 'sort_order']);
        });

        Schema::create('attribute_value_product', function (Blueprint $table) {
            $table->foreignId('attribute_value_id')->constrained('attribute_values')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->primary(['attribute_value_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_value_product');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
    }
};
