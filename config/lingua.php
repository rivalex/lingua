<?php

return [

    'lang_dir' => lang_path(),

    'default_locale' => config('app.locale', 'en'),

    'middleware' => ['web'],

    'routes_prefix' => 'lingua',

    'session_variable' => 'locale',

    /*
     * Language lines will be fetched by these loaders. You can put any class here that implements
     * the Spatie\TranslationLoader\TranslationLoaders\TranslationLoader-interface.
     */
    'translation_loaders' => [
        \Rivalex\Lingua\Database\Db::class,
    ],

    /*
     * This is the model used by the Db Translation loader. You can put any model here
     * that extends Spatie\TranslationLoader\LanguageLine.
     */
    'model' => \Rivalex\Lingua\Models\Translation::class,

    /*
     * This is the translation manager which overrides the default Laravel `translation.loader`
     */
    'translation_manager' => \Rivalex\Lingua\TranslationManager\LinguaManager::class,

    'editor' => [
        'headings' => false,
        'bold' => true,
        'italic' => true,
        'underline' => true,
        'strikethrough' => false,
        'subscript' => true,
        'superscript' => true,
        'blockquote' => false,
        'code-line' => false,
        'code-block' => false,
        'bullet' => true,
        'ordered' => true,
        'clear' => true,
        'code-mode' => false
    ]

];
