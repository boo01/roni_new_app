<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('id');
            $table->string('meta_title')->nullable()->after('logo');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->string('whatsapp')->nullable()->after('contact_email');
            $table->json('social_links')->nullable()->after('whatsapp');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['logo', 'meta_title', 'meta_description', 'whatsapp', 'social_links']);
        });
    }
};
