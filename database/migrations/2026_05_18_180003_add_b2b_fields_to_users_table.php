<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('customer_group_id')->nullable()->after('id')->constrained('customer_groups')->nullOnDelete();
            $table->string('phone')->nullable()->after('email');
            $table->string('company_name')->nullable()->after('phone');
            $table->string('company_tax_id')->nullable()->after('company_name');
            $table->string('address')->nullable()->after('company_tax_id');
            $table->boolean('is_active')->default(true)->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['customer_group_id']);
            $table->dropColumn([
                'customer_group_id',
                'phone',
                'company_name',
                'company_tax_id',
                'address',
                'is_active',
            ]);
        });
    }
};
