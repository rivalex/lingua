<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Rivalex\Lingua\Enums\LinguaType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('language_lines');
        Schema::create('language_lines', function (Blueprint $table) {
            $table->id();
            $table->string('group')->index()->comment('The translation\'s group.');
            $table->string('key')->comment('The translation\'s key.');
            $table->string('group_key')->unique()->comment('The UNIQUE group.key translation reference.');
            $table->string('type', 10)->default(LinguaType::text)->comment('The translation\'s type such as text, html or markdown.');
            $table->json('text')->default(new Expression('(JSON_ARRAY())'))->comment('The translation\'s JSON localized text');
            $table->boolean('is_vendor')->default(false)->comment('Whether the translation came from a vendor package.');
            $table->string('vendor')->nullable()->comment('Vendor package name if translation comes from vendor.');
            $table->timestamps();

            $table->unique(['group', 'key'], 'unique_group_key');
            $table->index(['group', 'key'], 'group_key_index');
        });

        Schema::dropIfExists('languages');
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->comment('ISO 639-1 language code (e.g., \'en\', \'es\', \'fr\')');
            $table->string('regional', 10)->unique()->comment('Regional variant code (e.g., \'US\', \'GB\', \'MX\')');
            $table->string('type', 10)->comment('Language type classification');
            $table->string('name', 100)->comment('English name of the language (e.g., \'English\', \'Spanish\')');
            $table->string('native', 100)->comment('Native name of the language (e.g., \'English\', \'Español\')');
            $table->string('direction', 5)->comment('Text direction: \'ltr\' (left-to-right) or \'rtl\' (right-to-left)');
            $table->boolean('is_default')->default(false)->comment('Whether the language is the default language for the application.');
            $table->integer('sort')->default(0)->comment('Sort order of the language in the list of languages.');
            $table->timestamps();

            $table->unique(['code', 'regional'], 'unique_language_type');
            $table->index(['code', 'regional'], 'language_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('language_lines');
        Schema::dropIfExists('languages');
    }
};
