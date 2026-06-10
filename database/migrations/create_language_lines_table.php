<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Rivalex\Lingua\Enums\LinguaType;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('language_lines')) {
            return;
        }

        Schema::create('language_lines', function (Blueprint $table) {
            $table->id();
            $table->string('group', 200)->index()->comment('The translation\'s group.');
            $table->string('key', 300)->comment('The translation\'s key.');
            $table->string('group_key', 500)->unique()->comment('The UNIQUE group.key translation reference.');
            $table->string('type', 10)->default(LinguaType::text)->comment('The translation\'s type such as text, html or markdown.');
            // Note: nullable with no SQL default. The previous default expression
            // (JSON_ARRAY()) is MySQL/modern-PG/MSSQL-2022 syntax and broke the
            // multi-DB guarantee (PostgreSQL < 16 fails at migration time).
            // The model treats a null column as [] everywhere ($this->text ?? []).
            $table->json('text')->nullable()->comment('The translation\'s JSON localized text');
            $table->boolean('is_vendor')->default(false)->comment('Whether the translation came from a vendor package.');
            $table->string('vendor', 200)->nullable()->comment('Vendor package name if translation comes from vendor.');
            $table->timestamps();

            $table->unique(['group', 'key', 'is_vendor', 'vendor'], 'unique_group_key');
            $table->index(['group', 'key', 'is_vendor', 'vendor'], 'group_key_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('language_lines');
    }
};
