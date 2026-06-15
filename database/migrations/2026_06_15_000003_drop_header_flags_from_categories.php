<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The header is now driven by the menu manager (menu_items), so the
        // per-category header flags are redundant.
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['show_in_header', 'header_sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('show_in_header')->default(false)->after('visible_to_b2b');
            $table->unsignedInteger('header_sort_order')->default(0)->after('show_in_header');
        });
    }
};
