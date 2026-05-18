<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->primary(['category_id', 'product_id']);
            $table->index(['product_id', 'is_primary']);
        });

        DB::table('products')->whereNotNull('category_id')->orderBy('id')->each(function ($product) {
            DB::table('category_product')->insertOrIgnore([
                'category_id' => $product->category_id,
                'product_id' => $product->id,
                'is_primary' => true,
                'sort_order' => 0,
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['category_id', 'is_active', 'sort_order']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('id')->constrained('categories')->nullOnDelete();
        });

        DB::table('category_product')->where('is_primary', true)->orderBy('product_id')->each(function ($row) {
            DB::table('products')->where('id', $row->product_id)->update(['category_id' => $row->category_id]);
        });

        Schema::dropIfExists('category_product');
    }
};
