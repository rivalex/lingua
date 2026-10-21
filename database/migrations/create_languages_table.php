<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('languages')) {
            return;
        }

        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->comment('ISO 639-1 language code (e.g., \'en\', \'es\', \'fr\')');
            // Note: 'regional' does NOT have a standalone unique constraint.
            // The composite unique index 'unique_language_type' on [code, regional]
            // is the correct constraint — a standalone unique on 'regional' would
            // incorrectly prevent two languages from sharing the same regional code
            // (e.g., en_US and fr_US would both fail if 'US' were globally unique).
            $table->string('regional', 10)->comment('Regional variant code (e.g., \'en_US\', \'en_GB\')');
            $table->string('type', 10)->comment('Language type classification');
            $table->string('name', 100)->comment('English name of the language (e.g., \'English\', \'Spanish\')');
            $table->string('native', 100)->comment('Native name of the language (e.g., \'English\', \'Espanol\')');
            $table->string('direction', 5)->comment('Text direction: \'ltr\' (left-to-right) or \'rtl\' (right-to-left)');
            $table->boolean('is_default')->default(false)->comment('Whether the language is the default language for the application.');
            $table->integer('sort')->default(0)->comment('Sort order of the language in the list of languages.');
            $table->timestamps();

            $table->unique(['code', 'regional'], 'unique_language_type');
            $table->index(['code', 'regional'], 'language_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
