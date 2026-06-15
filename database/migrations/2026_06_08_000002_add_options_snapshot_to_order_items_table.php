<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Snapshot of the customer's chosen options at order time, e.g.
            // [{"attribute":"ფერი","value":"წითელი"}]. Kept as a snapshot so
            // the invoice/history survive later attribute edits or deletes.
            $table->json('options_snapshot')->nullable()->after('product_name_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('options_snapshot');
        });
    }
};
