<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lingua_settings')) {
            return;
        }

        Schema::create('lingua_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Dot-notation setting key, e.g. selector.show_flags');
            $table->text('value')->nullable()->comment('Stored as string; cast on retrieval using the type column');
            $table->string('type', 20)->default('string')->comment('One of: string | bool | int | json');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lingua_settings');
    }
};
