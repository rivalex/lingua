@if ($errors->any())
    <div {{ $attributes->class(['rounded-md bg-red-50 mb-4 p-4']) }}>
        <div class="flex">
            <div class="shrink-0">
                <i class="fa-duotone fa-hexagon-exclamation text-danger-600 dark:text-danger-400"></i>
            </div>
            <div class="ml-3">
                <flux:header level="3">
                    @choice('global.display_errors.header', count($errors->all()))
                </flux:header>
                <div class="mt-2 text-sm text-danger-700">
                    <ul role="list" class="list-disc space-y-1 ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{!! $error !!}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endif
