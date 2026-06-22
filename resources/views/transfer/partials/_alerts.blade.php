{{--
    Transfer alert bar — success + error messages.
    Variables: $successMessage (string|null), $errorMessage (string|null).
    Include at the top of export.blade.php and import.blade.php.
--}}

@if(!empty($successMessage))
    <div class="flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
        <flux:icon.check-circle class="mt-0.5 size-5 shrink-0" />
        <div>
            <p class="text-sm font-medium">{{ __('lingua::lingua.transfer.import.success') }}</p>
            <p class="mt-1 text-sm">{{ $successMessage }}</p>
        </div>
    </div>
@endif

@if(!empty($errorMessage))
    <div class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
        <flux:icon.exclamation-triangle class="mt-0.5 size-5 shrink-0" />
        <div>
            <p class="text-sm font-medium">{{ __('lingua::lingua.transfer.export.error') }}</p>
            <p class="mt-1 text-sm">{{ $errorMessage }}</p>
        </div>
    </div>
@endif
