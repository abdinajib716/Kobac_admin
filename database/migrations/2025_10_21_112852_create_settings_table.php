<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
        
        // Insert default settings
        DB::table('settings')->insert([
            ['key' => 'site_name', 'value' => 'Dashboard Cajiib Creative', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'theme_primary_color', 'value' => '#0a6679', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'theme_secondary_color', 'value' => '#1f2937', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'default_mode', 'value' => 'lite', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'lite_navbar_bg', 'value' => '#ffffff', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'lite_sidebar_bg', 'value' => '#ffffff', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'lite_navbar_text', 'value' => '#090909', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'lite_sidebar_text', 'value' => '#090909', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'dark_navbar_bg', 'value' => '#171f2e', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'dark_sidebar_bg', 'value' => '#171f2e', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'dark_navbar_text', 'value' => '#ffffff', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'dark_sidebar_text', 'value' => '#ffffff', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
