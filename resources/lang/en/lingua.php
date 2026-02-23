<?php

return [
    'global' => [
        'search' => 'Search...',
        'saved' => 'Saved',
        'save' => 'Save',
        'required' => 'Required',
        'close' => 'Close',
        'click_to_copy' => 'Click to copy',
        'confirm' => [
            'delete' => 'To proceed with the deletion, type "<strong>:confirm</strong>" in the following field and click the <strong>delete button</strong>.',
            'change' => 'To proceed with the changes, type "<strong>:confirm</strong>" in the following field and click the next button.',
            'irreversible_action' => 'This action is irreversible!',
        ],
        'no_results_found' => 'No results found',
        'per_page' => 'Par page',
        'confirm_placeholder' => 'Type :confirm to proceed',
    ],
    'languages' => [
        'title' => 'Languages',
        'subtitle' => 'Manage system languages',
        'actions' => [
            'update_lang' => 'Update LANG',
            'sync' => [
                'database' => 'Sync to Database',
                'local' => 'Sync to Local',
            ],
            'status' => [
                'sync_local_done' => 'All languages and translations have been successfully synced to Local files!',
                'sync_local_fail' => 'Error occurred while syncing to local files! Please check the error logs.',
                'sync_database_done' => 'All languages and translations have been successfully synced to Database!',
                'sync_database_done_fail' => 'Error occurred while syncing to Database! Please check the error logs.',
                'lang_updated' => 'All languages and translations have been successfully updated!',
                'lang_updated_fail' => 'Error occurred while updating languages! Please check the error logs.',
            ]
        ],
        'default' => [
            'button' => 'Set as DEFAULT',
            'alert' => 'You are setting <strong>:language</strong> as <strong>DEFAULT</strong> language for this project.<br/>Are you sure to continue?',
            'action' => 'Change default language to :language',
            'header' => 'Set <strong>:language</strong> as DEFAULT',
            'confirm' => 'SET :language AS DEFAULT LANGUAGE',
            'save' => [
                'success' => 'Language set as default!',
                'success_description' => 'The language <strong>:language</strong> has been set as default.',
                'error' => 'Language set default failed!',
                'error_description' => 'An error occurred while trying to set language as default.<br/>Error: :error',
            ],
        ],
        'table' => [
            'language' => 'Language',
            'status' => 'Status',
            'row' => [
                'default_language' => 'DEFAULT LANGUAGE',
                'strings_missing' => 'Missing: <strong>:count</strong>',
                'strings_total' => 'Total strings: <strong>:count</strong>',
                'strings_translated' => 'Translated: <strong>:count</strong>',
            ]
        ],
        'create' => [
            'action' => 'Add new Language',
            'header' => 'Add a new Language',
            'select' => 'Select Language',
            'placeholder' => 'Select available language...',
            'save' => [
                'success' => 'Language added successfully!',
                'success_description' => 'The language <strong>:language</strong> has been added successfully.',
                'error' => 'Language add failed!',
                'error_description' => 'An error occurred while trying to add the language.<br/>Error: :error',

                'new_language_added' => 'New language added successfully!',
                'new_language_added_fail' => 'Error occurred while adding new language!',
            ],
        ],
        'sort' => [
            'title' => 'Sort Languages.',
            'subtitle' => 'Drag and drop languages to reorder them.',
            'sorted' => 'Languages reordered successfully!',
            'sorted_fail' => 'Languages reorder failed!'
        ],
        'delete' => [
            'confirm' => 'DELETE :language',
            'alert' => 'Are you sure you want to delete <strong>:language</strong>?',
            'alert_translations' => 'This action will also delete all translations for <strong>:language</strong>.',
            'header' => 'Delete language <strong>:language</strong>',
            'action' => 'Delete :language',
        ]
    ],
    'translations' => [
        'header' => ':locale Translations',
        'subheader' => 'Manage translations for :locale language.',
        'group' => [
            'placeholder' => 'Select a group...',
            'all_groups' => 'All groups'
        ],
        'table' => [
            'columns' => [
                'group_key' => 'Group / Key',
                'default' => 'Default String',
                'translation' => 'Translation for',
                'actions' => 'Actions',
            ],
            'group_key' => 'Group / Key',
            'default_language' => 'Default',
            'local_translation' => 'Translation for',
            'show_only_missing' => 'Show <strong>only</strong> missing',
        ],
        'editor' => [
            'format' => 'Text format',
            'headings' => [
                'paragraph' => 'Paragraph',
                'header-1' => 'Header 1',
                'header-2' => 'Header 2',
                'header-3' => 'Header 3',
                'quote' => 'Quote',
                'code' => 'Code',
            ],
            'bold' => 'Bold',
            'italic' => 'Italic',
            'underline' => 'Underline',
            'strikethrough' => 'Strikethrough',
            'ordered' => 'Ordered List',
            'bullet' => 'Bullet List',
            'code' => 'Toggle Code View',
            'code-line' => 'Code',
            'code-block' => 'Code Block',
            'subscript' => 'Subscript',
            'superscript' => 'Superscript',
            'clear' => 'Clear Formatting',
            'code-mode' => 'Viewing Source Code',
            'undo' => 'Undo',
            'redo' => 'Redo',
            'helper_html' => 'Focus out of this editor to save your HTML changes.',
            'helper_markdown' => 'Focus out of this editor to save your MD changes.',
            'helper_text' => 'Focus out of this textarea to save your changes.'
        ],
        'attributes' => [
            'translation_type' => 'Translation Type',
            'text_value' => 'Text Value',
            'html_value' => 'HTML Value',
            'md_value' => 'Markdown Value',
        ],
        'validation' => [
            'required' => 'This :attribute is required',
            'string' => 'This :attribute must be a string',
            'min' => 'This :attribute must be at least :min characters',
            'lang_updated_fail' => 'Failed to update language translation'
        ],
        'create' => [
            'action' => 'Add a new translation',
            'header' => 'Create new translation',
            'fields' => [
                'group' => 'Translations Group',
                'group_placeholder' => 'Select/Create a group...',
                'type' => 'Type',
                'type_placeholder' => 'Select a translation type...',
                'key' => 'Translation Key',
                'key_placeholder' => 'Enter a UNIQUE translation key...',
                'textValue' => 'Translation Value',
                'textValue_placeholder' => 'Enter a text translation...',
                'htmlValue' => 'Translation HTML Value',
                'htmlValue_placeholder' => 'Enter an HTML translation...',
                'mdValue' => 'Translation Markdown Value',
                'mdValue_placeholder' => 'Enter a Markdown translation...',
            ]
        ],
        'delete' => [
            'confirm_locale' => 'DELETE :locale TRANSLATION',
            'action' => 'Delete translation',
            'action_translation_locale' => 'Delete <strong>:locale</strong> translation',
            'header_locale' => 'Delete <strong>:locale</strong> translation',
            'confirm' => 'DELETE TRANSLATION',
            'header' => 'Delete translation',
            'alert' => 'Are you sure you want to delete <strong>:key</strong>?',
            'alert_translations' => 'All translations will be deleted as well.',
        ],
    ]
];
