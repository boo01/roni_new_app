<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('visible_to_retail')->default(true)->after('is_active');
            $table->boolean('visible_to_b2b')->default(true)->after('visible_to_retail');
            $table->boolean('show_in_header')->default(false)->after('visible_to_b2b');
            $table->unsignedInteger('header_sort_order')->default(0)->after('show_in_header');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('visible_to_retail')->default(true)->after('is_active');
            $table->boolean('visible_to_b2b')->default(true)->after('visible_to_retail');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['visible_to_retail', 'visible_to_b2b', 'show_in_header', 'header_sort_order']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['visible_to_retail', 'visible_to_b2b']);
        });
    }
};
