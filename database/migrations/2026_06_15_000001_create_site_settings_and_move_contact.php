<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->json('locations')->nullable();
            $table->timestamps();
        });

        // Move contact data off the pages table into the single settings row.
        // Prefer the contact page's data, fall back to any page that has it.
        $source = DB::table('pages')
            ->where(function ($q) {
                $q->whereNotNull('contact_phone')
                    ->orWhereNotNull('contact_email')
                    ->orWhereNotNull('contact_locations');
            })
            ->orderByRaw("CASE WHEN slug = 'contact' THEN 0 ELSE 1 END")
            ->first();

        DB::table('site_settings')->insert([
            'id' => 1,
            'contact_phone' => $source->contact_phone ?? null,
            'contact_email' => $source->contact_email ?? null,
            'locations' => $source->contact_locations ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['contact_phone', 'contact_email', 'contact_locations']);
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->json('contact_locations')->nullable();
        });

        // Best-effort restore of contact data onto the contact page.
        $settings = DB::table('site_settings')->find(1);
        if ($settings) {
            DB::table('pages')->where('slug', 'contact')->update([
                'contact_phone' => $settings->contact_phone,
                'contact_email' => $settings->contact_email,
                'contact_locations' => $settings->locations,
            ]);
        }

        Schema::dropIfExists('site_settings');
    }
};
