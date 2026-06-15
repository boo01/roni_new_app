<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->string('location')->default('header'); // header now; footer later
            $table->string('type')->default('link');        // page | category | link
            $table->string('label')->nullable();            // overrides the referenced title
            $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('url')->nullable();              // custom link
            $table->boolean('target_blank')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $this->seedFromHeaderCategories();
    }

    /**
     * Recreate the current auto-header (categories flagged show_in_header,
     * with their children) as an editable menu, so nothing disappears.
     */
    private function seedFromHeaderCategories(): void
    {
        if (! Schema::hasColumn('categories', 'show_in_header')) {
            return;
        }

        $roots = DB::table('categories')
            ->where('show_in_header', true)
            ->where('is_active', true)
            ->orderBy('header_sort_order')
            ->orderBy('name_ka')
            ->get();

        $order = 0;
        foreach ($roots as $root) {
            $parentId = DB::table('menu_items')->insertGetId([
                'location' => 'header',
                'type' => 'category',
                'category_id' => $root->id,
                'is_active' => true,
                'sort_order' => $order++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $children = DB::table('categories')
                ->where('parent_id', $root->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name_ka')
                ->get();

            $childOrder = 0;
            foreach ($children as $child) {
                DB::table('menu_items')->insert([
                    'parent_id' => $parentId,
                    'location' => 'header',
                    'type' => 'category',
                    'category_id' => $child->id,
                    'is_active' => true,
                    'sort_order' => $childOrder++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
