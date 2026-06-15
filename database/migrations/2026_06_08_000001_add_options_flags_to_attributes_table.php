<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            // When true, customers pick one of a product's values for this
            // attribute at purchase time (e.g. Color). Independent of
            // is_filterable, which controls the storefront sidebar filter.
            $table->boolean('is_selectable')->default(false)->after('is_filterable');
            // When true (and selectable), a choice is mandatory before the
            // product can be added to the cart.
            $table->boolean('is_required')->default(false)->after('is_selectable');
        });
    }

    public function down(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            $table->dropColumn(['is_selectable', 'is_required']);
        });
    }
};
